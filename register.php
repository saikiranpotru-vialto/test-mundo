<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Service\ConfigService;

header("Content-Type: application/json");
$response = [
    "status" => "error",
    "success" => false,
];

$email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);

if(filter_var($email, FILTER_VALIDATE_EMAIL)) {

    require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
    $allowedDomains = \Service\ConfigService::getAllowedRegistrationDomains();
    $emailDomain = substr(strrchr($email, "@"), 1);

    // Delete old tokens/registrations
    $entityManager = \Service\DatabaseService::getEntityManager();
    /** @var \Repository\RegistrationRepository $repository */
    $repository = $entityManager->getRepository("Entity\\Registration");
    $repository->deleteInvalid();


    if(in_array($emailDomain, $allowedDomains)) {
        try {
            $registration = new \Entity\Registration();
            $registration->setHash(bin2hex(random_bytes(16)));
            $registration->setValidUntil(new \DateTime("+1 day"));

            $entityManager = \Service\DatabaseService::getEntityManager();
            $entityManager->persist($registration);
            $entityManager->flush();

            $language = filter_input(INPUT_POST, "language", FILTER_SANITIZE_STRING) ?? "de";
            if(\Service\MailService::sendRegistrationMail($email, $registration, $language)) {
                $response['status'] = "email_sent";
                $response['success'] = true;
            } else {
                $response['status'] = "email_not_sent";
            }

        } catch (\Exception $e) {
            $logger = new Logger('registration');
            $logger->pushHandler(new StreamHandler(ConfigService::getLogDir(true, true) . 'log.log', Logger::WARNING));
            $logger->critical("unable to generate or send registration hash.", ["msg" => $e->getMessage(), "trace" => $e->getTrace()]);
            $response['status'] = "exception";
        }
    } else {
        $response['status'] = "invalid_email";
    }
} else {
    $response["status"] = "email_missing";
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>

