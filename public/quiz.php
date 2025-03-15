<?php
session_start();

// 1) Include composer autoload, DB config, etc.
require __DIR__ . '/../vendor/autoload.php';
include '../config/connect.php';

use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;

// ------------------------------------------------------
// A) Fetch user data (if logged in via cookie)
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

// Basic check for pdf_id
if (!isset($_GET['pdf_id'])) {
    header('Location: index.php');
    exit;
}
$pdf_id = $_GET['pdf_id'];

// Instantiate Guzzle client and PDF parser
$client = new Client([
    'verify' => false, // For debugging only; remove in production!
]);
$parser = new Parser();

// Retrieve the PDF filename from your database
$get_pdf = $conn->prepare("SELECT file FROM content WHERE id = ? LIMIT 1");
$get_pdf->execute([$pdf_id]);
if ($get_pdf->rowCount() === 0) {
    echo "No PDF found for this content.";
    exit;
}
$fetch_pdf   = $get_pdf->fetch(PDO::FETCH_ASSOC);
$pdfFilename = $fetch_pdf['file'];

// Build the full file path (assuming "uploads" folder is in the same directory as this file)
$pdfPath = __DIR__ . '/uploads/' . $pdfFilename;
if (!file_exists($pdfPath)) {
    echo "PDF file not found on server. Looking at: " . $pdfPath;
    exit;
}

// Extract text from the PDF using smalot/pdfparser
try {
    $pdf = $parser->parseFile($pdfPath);
    $pdfText = $pdf->getText();
} catch (Exception $e) {
    echo "Could not extract PDF text: " . $e->getMessage();
    exit;
}

// Build the payload for the Gemini API call
$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "Generate exactly 10 quiz questions based on the following PDF text. Each question must be multiple choice with exactly four options labeled A, B, C, and D, and must include one correct answer. Output the result in valid JSON format as an array, where each element is an object with these fields: 'question' (the quiz question), 'options' (an object with keys 'A', 'B', 'C', 'D' and their respective answer choices), and 'answer' (the letter corresponding to the correct option). Do not include any additional text or explanation. PDF text: $pdfText"
                ]
            ]
        ]
    ]
];

// Define the generation endpoint (adjust the model as needed)
$generateUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key={$geminiApiKey}";

try {
    $response = $client->post($generateUrl, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'json' => $payload
    ]);
} catch (Exception $e) {
    echo "Gemini API Generate error: " . $e->getMessage();
    exit;
}

$body = $response->getBody()->getContents();
// Uncomment the next line for debugging if needed:
// echo "<pre>API Response:\n" . htmlspecialchars($body) . "</pre>";

$responseData = json_decode($body, true);
$modelText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Remove triple backticks if present
$modelText = str_replace(['```json', '```'], '', $modelText);
$modelText = trim($modelText);

// Decode the modelText into an array of quiz questions.
$quizData = json_decode($modelText, true);
if (empty($quizData) || !is_array($quizData)) {
    // Fallback quiz data in case parsing fails.
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

// --- Processing Quiz Submission (if any) ---
$score = null;
$totalQs = 0;
$quizResults = null;

if (isset($_POST['submit_quiz']) && isset($_POST['quizData'])) {
    $quizData = json_decode($_POST['quizData'], true);
    if (!is_array($quizData)) {
        echo "Invalid quiz data.";
        exit;
    }
    $quizData = array_values($quizData);
    $totalQs = count($quizData);
    $score = 0;
    $quizResults = [];
    for ($i = 0; $i < $totalQs; $i++) {
        $userAnswer = $_POST['answer_' . $i] ?? '';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body class="quiz-page">
    <?php include 'components/user_header.php'; ?>

    <h1>Quiz</h1>

    <?php if (!isset($_POST['submit_quiz'])): ?>
        <?php $quizDataForDisplay = array_values($quizData); ?>
        <?php if (!empty($quizDataForDisplay)): ?>
            <form method="post" action="">
                <!-- Embed the quiz JSON in a hidden field so we can verify answers on submit -->
                <input type="hidden" name="quizData" value="<?= htmlspecialchars(json_encode($quizDataForDisplay)); ?>">
                <?php foreach ($quizDataForDisplay as $index => $qData): ?>
                    <div class="quiz-box">
                        <h3 class="quiz-title">Question <?= $index + 1; ?>:</h3>
                        <div class="quiz-question">
                            <p><?= htmlspecialchars($qData['question'] ?? ''); ?></p>
                        </div>
                        <?php if (!empty($qData['options']) && is_array($qData['options'])): ?>
                            <ul id="quiz-options">
                                <?php foreach ($qData['options'] as $letter => $optText): ?>
                                    <li>
                                        <input type="radio" name="answer_<?= $index; ?>" value="<?= $letter; ?>" id='answer_<?= $index; ?>" value="<?= $letter; ?>' required>
                                        <label for='answer_<?= $index; ?>" value="<?= $letter; ?>' >
                                            <?= htmlspecialchars("$letter) $optText"); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No options found.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="submit_quiz" class="submit-btn">Submit Quiz</button>
            </form>
        <?php else: ?>
            <p class="empty">No questions available. Something went wrong!</p>
        <?php endif; ?>
    <?php elseif ($score !== null && $quizResults !== null): ?>
        <div class="results">
            <h2>Your Score: <?= $score; ?> / <?= $totalQs; ?></h2>
            <?php foreach ($quizResults as $i => $result): ?>
                <div class="result-item">
                    <strong>Q<?= $i + 1; ?>:</strong> <?= htmlspecialchars($result['question']); ?><br>
                    Your answer: <strong><?= htmlspecialchars($result['user_answer']); ?></strong><br>
                    Correct answer: <strong><?= htmlspecialchars($result['correct_answer']); ?></strong><br>
                    <?php if ($result['is_correct']): ?>
                        <span style="color: green;">Correct!</span>
                    <?php else: ?>
                        <span style="color: red;">Incorrect.</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post">
            <button type="submit" name="refresh_quiz" class="submit-btn">Take Another Quiz</button>
        </form>
    <?php endif; ?>

    <?php
    if (isset($_POST['refresh_quiz'])) {
        header("Location: quiz.php?pdf_id=" . urlencode($pdf_id));
        exit;
    }
    ?>
    <script src="js/script.js"></script>
</body>
</html>
