<?php
session_start();

// 1) Include composer autoload, DB config, etc.
require __DIR__ . '/../vendor/autoload.php'; 
include '../config/connect.php';

// ------------------------------------------------------
// A) Fetch the user data (if logged in via cookie)
// ------------------------------------------------------
if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ? LIMIT 1");
    $select_user->execute([$user_id]);
    if ($select_user->rowCount() > 0) {
        $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
    } else {
        $fetch_user = [];
    }
} else {
    $user_id = '';
    $fetch_user = [];
}

// Load Gemini API key from your .env or fallback
$geminiApiKey = $_ENV['GEMINI_API'] ?? 'YOUR_API_KEY';

// Basic checks
if (!isset($_GET['pdf_id'])) {
   header('Location: index.php');
   exit;
}
$pdf_id = $_GET['pdf_id'];

// Initialize quiz session array if needed
if (!isset($_SESSION['quiz'])) {
    $_SESSION['quiz'] = [];
}

/* ------------------------------------------------------------------
   1) If we haven't generated a quiz yet for this PDF, do the 2-step
      approach: (A) Upload the PDF, (B) Generate quiz via fileUri.
   ------------------------------------------------------------------ */
if (!isset($_SESSION['quiz'][$pdf_id])) {

    // A) Locate the PDF file for this content in your uploads folder
    $get_pdf = $conn->prepare("SELECT file FROM content WHERE id = ? LIMIT 1");
    $get_pdf->execute([$pdf_id]);

    if ($get_pdf->rowCount() === 0) {
        echo "No PDF found for this content.";
        exit;
    }
    $fetch_pdf   = $get_pdf->fetch(PDO::FETCH_ASSOC);
    $pdfFilename = $fetch_pdf['file']; // e.g. "myfile.pdf"

    // Make sure the file exists
    $pdfPath = __DIR__ . '/uploads/' . $pdfFilename;
    if (!file_exists($pdfPath)) {
        echo "PDF file not found on server. Looking at: " . $pdfPath;
        exit;
    }

    // ----------------------------------------------------------------
    // (A) UPLOAD PDF -> Get fileUri
    // ----------------------------------------------------------------
    $uploadUrl = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$geminiApiKey}";

    $pdfBinary = file_get_contents($pdfPath);
    $numBytes  = filesize($pdfPath);
    $mimeType  = "application/pdf";

    // JSON part (telling Gemini the display name)
    $jsonPart = json_encode([
        "file" => [
            "display_name" => $pdfFilename
        ]
    ]);

    // We'll send a multipart/related body in a single request:
    $boundary = "----Boundary" . uniqid();
    $body  = "--$boundary\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= $jsonPart . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: $mimeType\r\n";
    $body .= "Content-Length: $numBytes\r\n";
    $body .= "Content-Transfer-Encoding: binary\r\n\r\n";
    $body .= $pdfBinary . "\r\n";
    $body .= "--$boundary--\r\n";

    // cURL handle to upload the PDF
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    // Headers from the example
    $uploadHeaders = [
        "X-Goog-Upload-Command: start, upload, finalize",
        "X-Goog-Upload-Header-Content-Length: $numBytes",
        "X-Goog-Upload-Header-Content-Type: $mimeType",
        "Content-Type: multipart/related; boundary=$boundary"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $uploadHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // (Optional) Make cURL verbose for debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $uploadVerbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $uploadVerbose);

    // Optional: disable SSL verification if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    // Execute the upload
    $uploadResponse = curl_exec($ch);
    $curlErr        = curl_error($ch);
    // HTTP status code
    $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Read cURL verbose logs
    rewind($uploadVerbose);
    $verboseLog1 = stream_get_contents($uploadVerbose);
    fclose($uploadVerbose);

    // Print debug info:
    echo "<h3>Upload Step Debug</h3>";
    echo "<pre>HTTP Code: $httpCode\n";
    echo "cURL Error: $curlErr\n";
    echo "Verbose Log:\n" . htmlspecialchars($verboseLog1) . "</pre>";
    echo "<pre>Raw Upload Response:\n" . htmlspecialchars($uploadResponse) . "</pre>";

    if ($curlErr) {
        echo "Gemini API Upload error: $curlErr";
        exit;
    }

    if ($httpCode !== 200) {
        echo "Upload step got HTTP code $httpCode, not 200.<br>Cannot proceed.";
        exit;
    }

    // We expect JSON with something like {"file":{"uri": "projects/..."}}
    $uploadJson = json_decode($uploadResponse, true);
    if (!isset($uploadJson['file']['uri'])) {
        echo "No fileUri returned from upload!<br>Response was: $uploadResponse";
        exit;
    }

    $fileUri = $uploadJson['file']['uri'];

    // ----------------------------------------------------------------
    // (B) GENERATE QUIZ -> referencing fileUri
    // ----------------------------------------------------------------
    $generateUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key={$geminiApiKey}";

    $requestBody = [
        "contents" => [
            [
              "role"  => "user",
              "parts" => [
                [
                  "fileData" => [
                    "fileUri"  => $fileUri,
                    "mimeType" => "application/pdf"
                  ]
                ],
                [
                  "text" => "Generate EXACTLY 10 multiple-choice questions from this PDF, each with 4 options (A,B,C,D) and a single correct answer. Return valid JSON like: \n\n[\n  {\n    \"question\": \"...\",\n    \"options\": {\n      \"A\": \"...\", \"B\": \"...\", \"C\": \"...\", \"D\": \"...\"\n    },\n    \"answer\": \"A\"\n  },\n  ...\n]\nNo extra text, just JSON!"
                ]
              ]
            ]
        ],
        "generationConfig" => [
            "temperature"      => 1,
            "topK"             => 40,
            "topP"             => 0.95,
            "maxOutputTokens"  => 8192,
            "responseMimeType" => "text/plain"
        ]
    ];

    $jsonRequest = json_encode($requestBody);

    $ch2 = curl_init($generateUrl);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $jsonRequest);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

    // Debug
    curl_setopt($ch2, CURLOPT_VERBOSE, true);
    $genVerbose = fopen('php://temp', 'w+');
    curl_setopt($ch2, CURLOPT_STDERR, $genVerbose);

    // Optional: disable SSL checks
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);

    $response2 = curl_exec($ch2);
    $curlErr2  = curl_error($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    // Read gen step logs
    rewind($genVerbose);
    $verboseLog2 = stream_get_contents($genVerbose);
    fclose($genVerbose);

    // Print debug info:
    echo "<h3>Generate Step Debug</h3>";
    echo "<pre>HTTP Code: $httpCode2\n";
    echo "cURL Error: $curlErr2\n";
    echo "Verbose Log:\n" . htmlspecialchars($verboseLog2) . "</pre>";
    echo "<pre>Raw Generate Response:\n" . htmlspecialchars($response2) . "</pre>";

    if ($curlErr2) {
        echo "Gemini API cURL error: $curlErr2";
        exit;
    }
    if ($httpCode2 !== 200) {
        echo "Generate step got HTTP code $httpCode2, not 200.<br>Cannot proceed.";
        exit;
    }

    $responseData = json_decode($response2, true);

    // We expect something like:
    // {
    //   "contents": [
    //     {
    //       "role": "model",
    //       "parts": [
    //         {"text": "..."} <-- the MCQ JSON
    //       ]
    //     }
    //   ]
    // }
    $modelText = '';
    if (isset($responseData['contents'][0]['parts'][0]['text'])) {
        $modelText = $responseData['contents'][0]['parts'][0]['text'];
    } else {
        echo "No text returned from Gemini!<br>Response was: $response2";
        exit;
    }

    // Attempt to parse the JSON array
    $quizData = json_decode($modelText, true);

    // If the model didn't return a valid array, fallback
    if (empty($quizData) || !is_array($quizData)) {
        $quizData = [
          [
            "question" => "Sample fallback question",
            "options"  => [
               "A" => "Option A",
               "B" => "Option B",
               "C" => "Option C",
               "D" => "Option D"
            ],
            "answer"   => "A"
          ]
        ];
    }

    // Store the final quiz array in the session
    $_SESSION['quiz'][$pdf_id] = $quizData;
}

/* ------------------------------------------------------------------
   2) If the user just submitted answers, let's calculate the score
   ------------------------------------------------------------------ */
$score       = null;
$totalQs     = 0;
$quizResults = null;

if (isset($_POST['submit_quiz']) && isset($_SESSION['quiz'][$pdf_id])) {
    // Force numeric indexes so "$index+1" won't cause errors
    $quizData = array_values($_SESSION['quiz'][$pdf_id]);
    
    $totalQs  = count($quizData);
    $score    = 0;
    $quizResults = [];

    for ($i = 0; $i < $totalQs; $i++) {
        // The user’s answer
        $userAnswer   = $_POST['answer_' . $i] ?? '';
        $correctAnswer = $quizData[$i]['answer'] ?? '';

        $isCorrect = (strtoupper($userAnswer) === strtoupper($correctAnswer));
        if ($isCorrect) {
            $score++;
        }

        $quizResults[] = [
          'question'       => $quizData[$i]['question'] ?? '',
          'user_answer'    => $userAnswer,
          'correct_answer' => $correctAnswer,
          'is_correct'     => $isCorrect
        ];
    }
}

/* ------------------------------------------------------------------
   3) Display the quiz or the results
   ------------------------------------------------------------------ */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz</title>

    <!-- Your styles -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>

<!-- Include your user header -->
<?php include 'components/user_header.php'; ?>

<h1>Quiz</h1>

<?php if (isset($_SESSION['quiz'][$pdf_id]) && !$score): ?>
    <!-- Show quiz form if no final score yet -->
    <?php 
    $quizData = array_values($_SESSION['quiz'][$pdf_id]); 
    ?>
    <?php if (!empty($quizData)): ?>
        <form method="post" action="">
        <?php foreach ($quizData as $index => $qData): ?>
            <div style="margin-bottom: 20px;">
                <h3>Question <?= $index+1; ?>:</h3>
                <p><?= htmlspecialchars($qData['question'] ?? ''); ?></p>

                <?php
                // "options": { "A": "...", "B": "...", ... }
                if (!empty($qData['options']) && is_array($qData['options'])):
                    foreach ($qData['options'] as $letter => $optText):
                ?>
                        <label>
                          <input type="radio" name="answer_<?= $index; ?>" value="<?= $letter; ?>" required>
                          <?= htmlspecialchars("$letter) $optText"); ?>
                        </label><br>
                <?php
                    endforeach;
                else:
                    echo "<p>No options found.</p>";
                endif;
                ?>
            </div>
            <hr>
        <?php endforeach; ?>

        <button type="submit" name="submit_quiz">Submit Quiz</button>
        </form>
    <?php else: ?>
        <p>No questions available. Something went wrong!</p>
    <?php endif; ?>

<?php elseif ($score !== null && $quizResults !== null): ?>
    <!-- Show final results -->
    <h2>Your Score: <?= $score; ?> / <?= $totalQs; ?></h2>
    <div>
    <?php foreach ($quizResults as $i => $result): ?>
        <div style="margin-bottom: 15px;">
            <strong>Q<?= $i+1; ?>:</strong> <?= htmlspecialchars($result['question']); ?><br>
            Your answer: <strong><?= htmlspecialchars($result['user_answer']); ?></strong><br>
            Correct answer: <strong><?= htmlspecialchars($result['correct_answer']); ?></strong><br>
            <?php if ($result['is_correct']): ?>
                <span style="color: green;">Correct!</span>
            <?php else: ?>
                <span style="color: red;">Incorrect.</span>
            <?php endif; ?>
        </div>
        <hr>
    <?php endforeach; ?>
    </div>

    <!-- Button to retake or re-generate a new quiz -->
    <form method="post">
        <button type="submit" name="refresh_quiz">Take Another Quiz</button>
    </form>

<?php else: ?>
    <!-- If no quiz data at all, or something's off -->
    <p>No quiz data. <a href="watch_video.php?get_id=<?= urlencode($pdf_id); ?>">Go back</a></p>
<?php endif; ?>

<?php
// 4) If user clicked 'Take Another Quiz', clear session so we can generate a new quiz
if (isset($_POST['refresh_quiz'])) {
    unset($_SESSION['quiz'][$pdf_id]);
    header("Location: quiz.php?pdf_id=" . urlencode($pdf_id));
    exit;
}
?>

</body>
</html>
