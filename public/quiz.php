<?php
session_start();
include '../config/connect.php';  // So we can locate PDF files, etc.

// 0) Basic checks
if (!isset($_GET['pdf_id'])) {
   // If there's no pdf_id, redirect or show an error
   header('Location: home.php');
   exit;
}
$pdf_id = $_GET['pdf_id'];

// ---------------------------------------------------------------------
// 1) If we haven't generated a quiz yet for this PDF, do so now
//    (You might want to let them regenerate a quiz by clearing the session, etc.)
// ---------------------------------------------------------------------
if (!isset($_SESSION['quiz'][$pdf_id])) {

    // -------------------------------------------------
    // 1a) Find the PDF file associated with this content ID
    // -------------------------------------------------
    $get_pdf = $conn->prepare("SELECT file FROM content WHERE id = ? LIMIT 1");
    $get_pdf->execute([$pdf_id]);

    if ($get_pdf->rowCount() === 0) {
        // No such PDF or content
        echo "No PDF found for this content.";
        exit;
    }
    $fetch_pdf   = $get_pdf->fetch(PDO::FETCH_ASSOC);
    $pdfFilename = $fetch_pdf['file']; // e.g. "myfile.pdf"
    $pdfPath     = __DIR__ . '/../uploads/' . $pdfFilename;

    if (!file_exists($pdfPath)) {
        echo "PDF file not found on server.";
        exit;
    }

    // -------------------------------------------------
    // 1b) Extract the text from the PDF (pseudo-code)
    // -------------------------------------------------
    // REPLACE this with your own actual text-extraction approach, for example:
    // $pdfText = (new Spatie\PdfToText\Pdf())->setPdf($pdfPath)->text();
    // Or a shell call to pdftotext, etc.
    // We'll do a very naive example:
    $pdfText = "Full text from your PDF goes here..."; 
    // e.g. 
    // $pdfText = shell_exec("pdftotext -enc UTF-8 ".escapeshellarg($pdfPath)." -");

    // -------------------------------------------------
    // 1c) Call your Gemini API to generate quiz questions
    // -------------------------------------------------
    // We'll do approximate code for a cURL call. 
    // Make sure your .env or environment loading is set up so $_ENV['GEMINI_API'] works.
    $geminiApiKey  = $_ENV['GEMINI_API'] ?? 'YOUR_GEMINI_API_KEY_HERE';
    $geminiEndpoint = 'https://api.gemini.example.com/v1/chat/completions'; 
    // The above is placeholder—replace with your actual endpoint.

    // Prepare the prompt or request body. We instruct the API to create exactly 10 MC questions.
    // We'll ask for a structured JSON in the response for easy parsing.
    $requestBody = [
      "model"   => "gemini-2.0-flash-lite",  // or whatever model Gemini uses
      "messages" => [
         [
            "role"    => "system",
            "content" => "You are a quiz generator. Given text from a PDF, create exactly 10 multiple-choice questions. 
                          Each question has exactly 4 answer options (A, B, C, D) and a single correct answer. 
                          Return valid JSON in this format:
                          {
                            \"questions\": [
                              {
                                \"question\": \"...\",
                                \"options\": [\"A) ...\", \"B) ...\", \"C) ...\", \"D) ...\"],
                                \"answer\": \"A\" 
                              },
                              ...
                            ]
                          }
                          No extra text. Just JSON."
         ],
         [
            "role"    => "user",
            "content" => $pdfText
         ]
      ]
    ];

    // Convert request to JSON
    $jsonRequest = json_encode($requestBody);

    // cURL to the Gemini API
    $ch = curl_init($geminiEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $geminiApiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute call
    $response = curl_exec($ch);
    curl_close($ch);

    // Attempt to decode the JSON from Gemini
    $quizData = [];
    if ($response) {
      // The actual structure of the Gemini response may vary:
      // Typically you'd find the "content" in a location like $responseArray['choices'][0]['message']['content'].
      // We'll do a generic approach:
      $responseArray = json_decode($response, true);
      
      // e.g. if the quiz is in $responseArray['choices'][0]['message']['content']:
      $rawQuizJson = $responseArray['choices'][0]['message']['content'] ?? '';

      // Now decode the quiz JSON (the string that the model created)
      $quizData = json_decode($rawQuizJson, true);

      // Safety check: ensure we have something like $quizData['questions']
      if (!isset($quizData['questions'])) {
         $quizData['questions'] = [];
      }
    }

    // If for some reason it's empty or invalid, fallback to a sample quiz
    if (empty($quizData['questions'])) {
        $quizData = [
          "questions" => [
            [
              "question" => "Sample question 1: (Could not retrieve from API)",
              "options"  => ["A) Sample A","B) Sample B","C) Sample C","D) Sample D"],
              "answer"   => "A"
            ],
            // ...
          ]
        ];
    }

    // Store the entire quiz data in $_SESSION so we can display it and check answers
    $_SESSION['quiz'][$pdf_id] = $quizData;
}

// ---------------------------------------------------------------------
// 2) If the user just submitted answers, let's calculate the score
// ---------------------------------------------------------------------
$score       = null;
$totalQs     = 0;
$quizResults = null;

if (isset($_POST['submit_quiz']) && isset($_SESSION['quiz'][$pdf_id])) {
    $quizData  = $_SESSION['quiz'][$pdf_id];
    $questions = $quizData['questions'];
    $totalQs   = count($questions);
    $score     = 0;
    $quizResults = [];

    // Loop over each question and compare
    for ($i = 0; $i < $totalQs; $i++) {
        $userAnswer    = $_POST['answer_' . $i] ?? '';
        $correctAnswer = $questions[$i]['answer'];

        $isCorrect = (strtoupper($userAnswer) === strtoupper($correctAnswer));
        if ($isCorrect) {
           $score++;
        }

        $quizResults[] = [
          'question'      => $questions[$i]['question'],
          'user_answer'   => $userAnswer,
          'correct_answer'=> $correctAnswer,
          'is_correct'    => $isCorrect
        ];
    }
}

// ---------------------------------------------------------------------
// 3) Display the quiz or the results
// ---------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Page</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Adjust your CSS path as needed -->
</head>
<body>

<h1>Quiz for PDF #<?= htmlspecialchars($pdf_id); ?></h1>

<?php if (isset($_SESSION['quiz'][$pdf_id]) && !$score): ?>
    <!-- Show the quiz form if we haven't scored yet -->
    <?php $quizData  = $_SESSION['quiz'][$pdf_id]; ?>
    <?php $questions = $quizData['questions'] ?? []; ?>

    <?php if (!empty($questions)): ?>
    <form action="" method="post">
        <?php foreach ($questions as $index => $qData): ?>
            <div class="question-block">
                <h3>Question <?= ($index+1) ?>:</h3>
                <p><?= htmlspecialchars($qData['question']); ?></p>
                
                <?php if (!empty($qData['options'])): ?>
                   <?php foreach ($qData['options'] as $optKey => $optionText): ?>
                       <!-- We'll guess the first letter is 'A)', 'B)', 'C)', 'D)' etc. -->
                       <?php
                         // For a more robust approach, parse out the letter from $optionText, 
                         // but we'll assume the letter is the first char:
                         $optionLetter = substr(trim($optionText), 0, 1); 
                       ?>
                       <label>
                          <input type="radio" name="answer_<?= $index; ?>" value="<?= $optionLetter; ?>" required>
                          <?= htmlspecialchars($optionText); ?>
                       </label>
                       <br>
                   <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <hr>
        <?php endforeach; ?>

        <button type="submit" name="submit_quiz">Submit Quiz</button>
    </form>
    <?php else: ?>
        <p>No questions found. Something went wrong.</p>
    <?php endif; ?>

<?php elseif ($score !== null && $quizResults !== null): ?>
    <!-- Show the user their score and correct answers -->
    <h2>Your Score: <?= $score; ?> / <?= $totalQs; ?></h2>
    <?php foreach ($quizResults as $i => $result): ?>
       <div class="question-block">
          <p><strong>Q<?= $i+1; ?>:</strong> <?= htmlspecialchars($result['question']); ?></p>
          <p>Your answer: <strong><?= htmlspecialchars($result['user_answer']); ?></strong></p>
          <p>Correct answer: <strong><?= htmlspecialchars($result['correct_answer']); ?></strong></p>
          <?php if ($result['is_correct']): ?>
             <p style="color: green;">Correct!</p>
          <?php else: ?>
             <p style="color: red;">Incorrect.</p>
          <?php endif; ?>
       </div>
       <hr>
    <?php endforeach; ?>

    <!-- Optionally provide a button to retake or refresh the quiz -->
    <form method="post">
        <button type="submit" name="refresh_quiz">Take Another Quiz</button>
    </form>

<?php else: ?>
    <!-- Fallback if no quiz in session at all -->
    <p>No quiz data found. <a href="watch_video.php?get_id=<?= urlencode($pdf_id); ?>">Go back</a></p>
<?php endif; ?>

<?php
// 4) If user clicked 'Take Another Quiz', clear session quiz data so a new quiz can be generated
if (isset($_POST['refresh_quiz'])) {
    unset($_SESSION['quiz'][$pdf_id]);
    // Reload this page to re-generate the quiz
    header("Location: quiz.php?pdf_id=".$pdf_id);
    exit;
}
?>

</body>
</html>
