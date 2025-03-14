<?php
include '../config/connect.php';

// Increase PHP execution time limit (to avoid timeout)
set_time_limit(300); // 5 minutes

// Check if a PDF ID is provided
if (!isset($_GET['pdf_id']) || empty($_GET['pdf_id'])) {
    die("No PDF selected for quiz generation.");
}

$pdf_id = $_GET['pdf_id'];

// Fetch the PDF file from the database
$select_pdf = $conn->prepare("SELECT file FROM content WHERE id = ?");
$select_pdf->execute([$pdf_id]);

if ($select_pdf->rowCount() > 0) {
    $fetch_pdf = $select_pdf->fetch(PDO::FETCH_ASSOC);
    $pdfFile = $fetch_pdf['file'];
    $pdfPath = realpath("uploads/" . $pdfFile); // Ensure absolute path
} else {
    die("PDF not found in the database.");
}

// API Configuration
$apiUrl = "https://api.arlinear.com/functions/v1/generate-quiz";
$apiKey = "59c415b6-ee29-42c9-b22e-142ea0e0e84a";

// Prepare cURL to send the PDF
$ch = curl_init();
$cfile = new CURLFile($pdfPath, 'application/pdf', basename($pdfPath));

$data = [
   'file' => $cfile, // Correct file format
   'numQuestions' => 5,
   'instructions' => 'Generate quiz questions based on the provided PDF.',
];

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
   'Authorization: ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Increase timeout to 5 minutes

// Execute API call
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("API request failed: " . curl_error($ch));
}

curl_close($ch);

// Decode API response
echo $response;
$quizData = json_decode($response, true);

if (!$quizData || empty($quizData['quizzes'])) {
    die("Failed to generate quiz questions.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Quiz</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="quiz-content">
    <h2 class="heading">Generated Quiz Questions</h2>

    <?php foreach ($quizData['quizzes'] as $quiz): ?>
        <div class="quiz-box">
            <h3 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h3>

            <?php foreach ($quiz['questions'] as $index => $question): ?>
                <div class="quiz-question">
                    <p><strong>Q<?= $index + 1 ?>:</strong> <?= htmlspecialchars($question['value']) ?></p>
                    
                    <!-- Check if it's a multiple-choice question -->
                    <?php if ($question['type'] === 'mc' && isset($question['choices'])): ?>
                        <ul class="quiz-options">
                            <?php foreach ($question['choices'] as $choice): ?>
                                <li><?= htmlspecialchars($choice['value']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <!-- If it's a short-answer question -->
                    <?php if ($question['type'] === 'short'): ?>
                        <p class="short-answer-hint"><em>Grading Criteria: <?= htmlspecialchars($question['gradingCriteria']) ?></em></p>
                        <textarea class="short-answer-box" placeholder="Enter your answer here..."></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>
