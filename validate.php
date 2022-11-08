<?php

$response = [
    "status" => "error",
    "success" => false,
];


try {
    $token = filter_input(INPUT_POST, "token", FILTER_SANITIZE_STRING);
    if(strlen($token)) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        $entityManager = \Service\DatabaseService::getEntityManager();
        /** @var \Repository\RegistrationRepository $repository */
        $repository = $entityManager->getRepository("Entity\\Registration");

        $registration = $repository->findOneBy(["hash" => $token ?? null]);
        if ($registration instanceof \Entity\Registration && $registration->getValidUntil() >= new \DateTime()) {
            $response['success'] = true;
            $response['status'] = "valid";
        } else {
            if ($registration instanceof \Entity\Registration) {
                $entityManager->remove($registration);
                $entityManager->flush();
                $response['status'] = 'expired';
            } else {
                $response['status'] = 'invalid';
            }
        }
    } else {
        $response['status'] = 'missing';
    }
} catch (\Exception $e) {
    $response['status'] = 'exception';
}
header("Content-Type: application/json");
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
