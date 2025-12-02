<?php
declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Repository\ContactRepository;
use App\Repository\PhoneRepository;
use App\Validation\Validator;
use PDOException;

final class ContactController
{
    private ContactRepository $contactRepo;
    private PhoneRepository $phoneRepo;

    public function __construct()
    {
        $this->contactRepo = new ContactRepository();
        $this->phoneRepo = new PhoneRepository();
    }

    public function list(): void
    {
        $request = new Request();
        $page = $request->queryInt('page', 1);
        $perPage = $request->queryInt('per_page', 10);
        $search = $request->queryString('search', '');

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $list = $this->contactRepo->list($search, $page, $perPage);
        $total = $this->contactRepo->count($search);

        $data = array_map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'address' => $c->address,
        ], $list);

        JsonResponse::ok(['data' => $data, 'total' => $total]);
    }

    public function get(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $contact = $this->contactRepo->get($id);

        if (!$contact) {
            JsonResponse::notFound('Contact not found');
            return;
        }

        $phones = $this->phoneRepo->listByContact($contact->id);

        JsonResponse::ok([
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'address' => $contact->address,
            'phones' => array_map(fn($p) => [
                'id' => $p->id,
                'number' => $p->number,
                'label' => $p->label,
            ], $phones),
        ]);
    }

    public function create(): void
    {
        $request = new Request();
        $data = $request->jsonAll();

        $errors = Validator::createContact($data);
        if ($errors) {
            JsonResponse::unprocessable($errors);
            return;
        }

        $phoneErrors = Validator::createPhones($data['phones'] ?? []);
        if ($phoneErrors) {
            JsonResponse::unprocessable($phoneErrors);
            return;
        }

        try {
            $contact = $this->contactRepo->create(
                trim((string)$data['name']),
                trim((string)$data['email']),
                isset($data['address']) ? trim((string)$data['address']) : null,
            );

            foreach ($data['phones'] as $ph) {
                $number = isset($ph['number']) ? trim((string)$ph['number']) : '';
                if ($number !== '') {
                    $label = isset($ph['label']) ? trim((string)$ph['label']) : null;
                    $this->phoneRepo->add($contact->id, $number, $label);
                }
            }

            JsonResponse::created(['id' => $contact->id]);
        } catch (PDOException $e) {
            if ($this->isDuplicateError($e)) {
                JsonResponse::conflict('Email already exists');
            } else {
                JsonResponse::internalError('Failed to create contact');
            }
        }
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $request = new Request();
        $data = $request->jsonAll();

        $contact = $this->contactRepo->get($id);
        if (!$contact) {
            JsonResponse::notFound('Contact not found');
            return;
        }

        if (isset($data['email']) && !Validator::email((string)$data['email'])) {
            JsonResponse::unprocessable(['email' => 'invalid']);
            return;
        }

        if (array_key_exists('address', $data)) {
            $addr = trim((string)$data['address']);
            if ($addr === '' || !Validator::address($addr)) {
                JsonResponse::unprocessable(['address' => 'invalid']);
                return;
            }
        }

        try {
            $updated = $this->contactRepo->update($id, $data);
            JsonResponse::ok(['id' => $updated?->id ?? $id]);
        } catch (PDOException $e) {
            if ($this->isDuplicateError($e)) {
                JsonResponse::conflict('Email already exists');
            } else {
                JsonResponse::internalError('Failed to update contact');
            }
        }
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!$this->contactRepo->get($id)) {
            JsonResponse::notFound('Contact not found');
            return;
        }

        $this->contactRepo->delete($id);
        JsonResponse::noContent();
    }

    public function addPhone(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $request = new Request();
        $data = $request->jsonAll();

        if (!$this->contactRepo->get($id)) {
            JsonResponse::notFound('Contact not found');
            return;
        }

        $number = isset($data['number']) ? trim((string)$data['number']) : '';
        if (!$number) {
            JsonResponse::unprocessable(['number' => 'required']);
            return;
        }

        if (!Validator::phone($number)) {
            JsonResponse::unprocessable(['number' => 'invalid']);
            return;
        }

        $label = isset($data['label']) ? trim((string)$data['label']) : null;
        if (!Validator::phoneLabel($label)) {
            JsonResponse::unprocessable(['label' => 'invalid']);
            return;
        }

        try {
            $phone = $this->phoneRepo->add($id, $number, $label);
            JsonResponse::created(['id' => $phone->id]);
        } catch (PDOException $e) {
            JsonResponse::internalError('Failed to add phone');
        }
    }

    public function deletePhone(array $params): void
    {
        $contactId = (int)($params['id'] ?? 0);
        $phoneId = (int)($params['phoneId'] ?? 0);

        if (!$this->contactRepo->get($contactId)) {
            JsonResponse::notFound('Contact not found');
            return;
        }

        $phones = $this->phoneRepo->listByContact($contactId);
        if (count($phones) <= 1) {
            JsonResponse::unprocessable(['phones' => 'at_least_one_required']);
            return;
        }

        $this->phoneRepo->delete($phoneId);
        JsonResponse::noContent();
    }

    private function isDuplicateError(PDOException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'UNIQUE')
            || str_contains($message, 'Duplicate entry')
            || $e->getCode() === '23000';
    }
}
