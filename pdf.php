<?php

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
//$data = json_decode(file_get_contents('php://input'), true);

if ($data['token']) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
    $entityManager = \Service\DatabaseService::getEntityManager();
    /** @var \Repository\RegistrationRepository $repository */
    $repository = $entityManager->getRepository("Entity\\Registration");
    $registration = $repository->findOneBy(["hash" => $data['token'] ?? null]);
    if ($registration instanceof \Entity\Registration && $registration->getValidUntil() >= new \DateTime()) {
        $pdfService = new \Service\PdfService();
        $pdfService->createForm($data, $data['language'] ?? 'de', false);
    } else {
        if ($registration instanceof \Entity\Registration) {
            $entityManager->remove($registration);
            $entityManager->flush();
        }
        header("HTTP/1.1 403 Not authorized");
    }
} else {
    header("HTTP/1.1 403 Not authorized");
}


//    header("Location", "./index.php");
header("HTTP/1.1 403 Not authorized");
