<?php

namespace App\Modules\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\DifusionModel;
use App\Modules\Services\DifusionService;

class DifusionController extends Controller
{
    public function sendBroadcast(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $subject = $data['subject'] ?? '';
        $message = $data['message'] ?? '';
        $filters = $data['filters'] ?? [];

        if (empty($subject) || empty($message)) {
            return Utils::responseJsonError($response, 'Asunto y mensaje obligatorios.', '', 400);
        }

        // 1. Obtener destinatarios
        $model = new DifusionModel();
        $recipients = $model->getRecipients($filters);


        if (empty($recipients)) {
            return Utils::responseJsonError($response, 'No hay destinatarios.', '', 404);
        }

        // 2. Enviar correos
        $service = new DifusionService();
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            $status = $service->sendEmail($user['email'], $user['nombre'], $subject, nl2br($message));
            if ($status) $sent++;
            else $failed++;
        }


        return Utils::responseJsonOk($response, [
            'message' => "Proceso finalizado.",
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($recipients)
        ]);
    }
}
