<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Core\Validator;
use App\Repositories\EventRepo;

final class EventController
{
    public const PER_PAGE = 20;

    /**
     * GET /events?from&to&type&status&q&page&scope
     */
    public function index(Request $request): Response
    {
        $user = Access::requireUser($request);
        $v = new Validator($request->query);
        $filters = [
            'from' => $v->date('from'),
            'to' => $v->date('to'),
            'type' => $v->enum('type', EventRepo::TYPES),
            'status' => $v->enum('status', EventRepo::STATUSES),
            'q' => $v->string('q', 100, false, 'Qidiruv'),
            'scope' => $v->enum('scope', ['upcoming', 'past', 'all'], 'all'),
        ];
        $page = $v->int('page', 1, 1000) ?? 1;
        $v->throwIfErrors();

        $result = Services::events()->search($filters, $page, self::PER_PAGE, Access::isOwner($user));
        return Response::ok([
            'items' => Services::financeGuard()->presentRows($result['rows']),
            'page' => $page,
            'has_more' => $result['has_more'],
        ]);
    }

    /**
     * GET /events/{id}
     */
    public function show(Request $request): Response
    {
        $user = Access::requireUser($request);
        return Response::ok($this->presentEvent((int) $request->param('id'), $user));
    }

    /**
     * POST /events
     */
    public function store(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = Services::eventService()->create($request->json(), $user['id'], $this->confirmFlag($request));
        return Response::ok($this->presentEvent($id, $user), 201);
    }

    /**
     * PUT /events/{id}
     */
    public function update(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = (int) $request->param('id');
        Services::eventService()->update($id, $request->json(), $user['id'], $this->confirmFlag($request));
        return Response::ok($this->presentEvent($id, $user));
    }

    public function cancel(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = (int) $request->param('id');
        Services::eventService()->cancel($id, $user['id']);
        return Response::ok($this->presentEvent($id, $user));
    }

    public function restore(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = (int) $request->param('id');
        Services::eventService()->restore($id, $user['id']);
        return Response::ok($this->presentEvent($id, $user));
    }

    public function archive(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = (int) $request->param('id');
        Services::eventService()->archive($id, $user['id']);
        return Response::ok($this->presentEvent($id, $user));
    }

    /**
     * @param array{role: string} $user
     * @return array<string, mixed>
     */
    private function presentEvent(int $id, array $user): array
    {
        $event = Services::events()->find($id);
        if ($event === null || ($event['status'] === 'archived' && !Access::isOwner($user))) {
            throw HttpError::notFound();
        }
        return Services::financeGuard()->present($event, $user, Services::payments()->paidCents($id));
    }

    private function confirmFlag(Request $request): bool
    {
        $value = $request->input('confirm_conflict', false);
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
