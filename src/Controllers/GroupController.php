<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Telegram\TelegramException;

/**
 * Guruhni ilovadan ulash va guruhga tugma yuborish — faqat owner.
 */
final class GroupController
{
    /**
     * GET /group — ulangan guruh holati.
     */
    public function show(Request $request): Response
    {
        Access::requireOwner($request);
        return Response::ok(Services::groupLinker()->status());
    }

    /**
     * POST /group/link — bir martalik kod va Telegram'dagi guruh tanlash havolasi.
     */
    public function link(Request $request): Response
    {
        $user = Access::requireOwner($request);
        return Response::ok(Services::groupLinker()->start($user['id']));
    }

    /**
     * POST /group/link/check — bot guruhga qo'shildimi (getUpdates).
     */
    public function check(Request $request): Response
    {
        Access::requireOwner($request);
        try {
            $result = Services::groupLinker()->processUpdates();
        } catch (TelegramException $e) {
            if ($e->errorCode === 409) {
                throw new HttpError(409, 'webhook_set', 'Botda webhook o‘rnatilgan. Uni o‘chiring: docs/DEPLOY.md, 5-qadam.');
            }
            throw new HttpError(502, 'telegram_error', 'Telegram bilan aloqa yo‘q. Birozdan keyin qayta urinib ko‘ring.');
        }
        return Response::ok(Services::groupLinker()->status() + ['just_linked' => $result['linked']]);
    }

    /**
     * POST /group/publish-button — guruhga "TO‘YLAR JADVALI" tugmali xabar + pin.
     */
    public function publishButton(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $result = Services::groupLinker()->publishButton();
        Services::audit()->log($user['id'], 'group.publish_button', 'settings', null, ['status' => $result['pinned'] ? 'pinned' : 'sent']);
        return Response::ok($result);
    }
}
