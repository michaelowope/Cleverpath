<?php
session_start();

// 1) Include composer autoload, DB config, etc.
require __DIR__ . '/../vendor/autoload.php'; 
include '../config/connect.php';

use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;

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

// Instantiate Guzzle client (disable SSL verification if needed)
$client = new Client([
    'verify' => false, // For debugging only; remove in production!
]);
$parser = new Parser();

$get_pdf = $conn->prepare("SELECT file FROM content WHERE id = ? LIMIT 1");
$get_pdf->execute([$pdf_id]);

if ($get_pdf->rowCount() === 0) {
    echo "No PDF found for this content.";
    exit;
}
$fetch_pdf   = $get_pdf->fetch(PDO::FETCH_ASSOC);
$pdfFilename = $fetch_pdf['file'];

$pdfPath = __DIR__ . '/uploads/' . $pdfFilename;
    if (!file_exists($pdfPath)) {
        echo "PDF file not found on server. Looking at: " . $pdfPath;
    exit;
}

$uploadUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key={$geminiApiKey}";
$pdf =  $parser->parseFile($pdfPath);
$pdftext = $pdf->getText();

$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "generate 10 quiz questions in json format, where the correct answer is provided from this: $pdftext",          
                ]
            ]
        ]
    ]
];

// Make the POST request with query parameters and JSON payload
$response = $client->post($uploadUrl, [
    'headers' => [
        'Content-Type' => 'application/json'
    ],
    'json' => $payload
]);

$body = $response->getBody()->getContents();
echo $body;
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
<body>

<?php include 'components/user_header.php'; ?>

<h1>Quiz</h1>

<?php if (isset($_SESSION['quiz'][$pdf_id]) && !$score): ?>
    <?php $quizData = array_values($_SESSION['quiz'][$pdf_id]); ?>
    <?php if (!empty($quizData)): ?>
        <form method="post" action="">
        <?php foreach ($quizData as $index => $qData): ?>
            <div style="margin-bottom: 20px;">
                <h3>Question <?= $index+1; ?>:</h3>
                <p><?= htmlspecialchars($qData['question'] ?? ''); ?></p>
                <?php if (!empty($qData['options']) && is_array($qData['options'])): ?>
                    <?php foreach ($qData['options'] as $letter => $optText): ?>
                        <label>
                          <input type="radio" name="answer_<?= $index; ?>" value="<?= $letter; ?>" required>
                          <?= htmlspecialchars("$letter) $optText"); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No options found.</p>
                <?php endif; ?>
            </div>
            <hr>
        <?php endforeach; ?>
        <button type="submit" name="submit_quiz">Submit Quiz</button>
        </form>
    <?php else: ?>
        <p>No questions available. Something went wrong!</p>
    <?php endif; ?>

<?php elseif ($score !== null && $quizResults !== null): ?>
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
    <form method="post">
        <button type="submit" name="refresh_quiz">Take Another Quiz</button>
    </form>
<?php else: ?>
    <p>No quiz data. <a href="watch_video.php?get_id=<?= urlencode($pdf_id); ?>">Go back</a></p>
<?php endif; ?>

<?php
if (isset($_POST['refresh_quiz'])) {
    unset($_SESSION['quiz'][$pdf_id]);
    header("Location: quiz.php?pdf_id=" . urlencode($pdf_id));
    exit;
}
?>

</body>
</html>
