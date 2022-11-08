<?php


$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
//$data = json_decode(file_get_contents('php://input'), true);

$response = [
    "status" => "error",
    "success" => false,
];

if ($data['token']) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
    $entityManager = \Service\DatabaseService::getEntityManager();
    /** @var \Repository\RegistrationRepository $repository */
    $repository = $entityManager->getRepository("Entity\\Registration");
    $registration = $repository->findOneBy(["hash" => $data['token'] ?? null]);
    if ($registration instanceof \Entity\Registration && $registration->getValidUntil() >= new \DateTime()) {
        $pdfService = new \Service\PdfService();
        $pdfData = $pdfService->createForm($data, $data['language'] ?? 'de');
        //TODO: add $language as second parameter?
        if(\Service\MailService::sendPdf($pdfData,$data['language'],'Siemens',$data['lastName'],$data['firstName'],$data['contractType'],$data['personnelNumber'])) {
            $response['success'] = true;
            $response['status'] = "mail_sent";
        } else {
            $response['status'] = "mail_not_sent";
        }
    } else {
        if ($registration instanceof \Entity\Registration) {
            $entityManager->remove($registration);
            $entityManager->flush();
        }
        $response['status'] = "invalid_token";
    }
} else {
    $response['status'] = "missing_token";
}

header("Content-Type: application/json");
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
