<?php

namespace BookneticApp\Backend\Staff\Services;

use BookneticApp\Backend\Base\Repository\TimesheetRepository;
use BookneticApp\Backend\Staff\DTOs\Request\StaffRequest;
use BookneticApp\Backend\Staff\DTOs\Response\StaffGetResponse;
use BookneticApp\Backend\Staff\DTOs\Response\StaffResponse;
use BookneticApp\Backend\Staff\Mappers\StaffMapper;
use BookneticApp\Providers\Services\CapabilityService;
use BookneticApp\Providers\Services\MediaService;
use BookneticApp\Providers\IoC\Attributes\Service;
use BookneticApp\Backend\Staff\Exceptions\{
    StaffLimitExceededException,
    StaffNotFoundException,
    StaffValidationException
};
use BookneticApp\Backend\Staff\Repository\AppointmentRepository;
use BookneticApp\Backend\Staff\Repository\StaffRepository;
use BookneticApp\Backend\Staff\Repository\ServiceStaffRepository;
use BookneticApp\Providers\Helpers\{Date, Helper};

#[Service]
class StaffService
{
    private StaffRepository $repository;
    private ServiceStaffRepository $serviceStaffRepository;
    private TimesheetRepository $timesheetRepository;
    private StaffRelationService $relationService;
    private StaffWpUerService $staffLoginService;
    private MediaService $mediaService;
    private CapabilityService $capabilityService;
    private AppointmentRepository $appointmentRepository;

    public function __construct(
        StaffRepository $repository,
        ServiceStaffRepository $serviceStaffRepository,
        TimesheetRepository $timesheetRepository,
        StaffRelationService $relationService,
        StaffWpUerService $staffLoginService,
        MediaService $mediaService,
        CapabilityService $capabilityService,
        AppointmentRepository $appointmentRepository
    ) {
        $this->repository = $repository;
        $this->serviceStaffRepository = $serviceStaffRepository;
        $this->timesheetRepository = $timesheetRepository;
        $this->relationService = $relationService;
        $this->staffLoginService = $staffLoginService;
        $this->mediaService = $mediaService;
        $this->capabilityService = $capabilityService;
        $this->appointmentRepository = $appointmentRepository;
    }

    /**
     * Creates or updates a staff record.
     *
     * @param StaffRequest $dto
     * @return int
     * @throws StaffValidationException
     * @throws StaffNotFoundException
     * @throws StaffLimitExceededException
     */
    public function save(StaffRequest $dto): int
    {
        $this->validate($dto);

        if (!$dto->isEdit()) {
            $this->checkAllowedStaffLimit();
        }

        if ($dto->isEdit()) {
            $this->update($dto);
        } else {
            $this->create($dto);
        }

        $this->relationService->saveAll($dto);

        $this->repository->handleTranslation($dto->id, $dto->translations);

        return $dto->id;
    }

    /**
     * Validates basic staff data before saving.
     *
     * @throws StaffValidationException
     */
    private function validate(StaffRequest $dto): void
    {
        if (empty($dto->name) || empty($dto->email)) {
            throw new StaffValidationException(
                bkntc__('Please fill in all required fields correctly!')
            );
        }
        if (empty($dto->locations) || count(array_filter($dto->locations)) === 0) {
            throw new StaffValidationException(bkntc__('Please select at least one location.'));
        }

        if ($dto->allowToLogin) {
            $this->staffLoginService->validateEmailForLogin($dto->email);
        }
    }

    /**
     * Creates a new staff member.
     *
     * @throws StaffValidationException
     */
    private function create(StaffRequest $dto): void
    {
        $userId = $this->staffLoginService->handle($dto);
        if ($dto->image) {
            $image = $this->handleProfileImage($dto->image);
        }

        $data = [
            'name'          => $dto->name,
            'user_id'       => $userId,
            'email'         => $dto->email,
            'phone_number'  => $dto->phone,
            'about'         => $dto->note,
            'profile_image' => $image ?? null,
            'locations'     => implode(',', $dto->locations ?? []),
            'profession'    => $dto->profession,
            'is_active'     => 1,
        ];

        $dto->id = $this->repository->insert($data);

        do_action('bkntc_staff_created', $dto->id);
    }

    /**
     * Updates an existing staff member.
     *
     * @throws StaffValidationException|StaffNotFoundException
     */
    private function update(StaffRequest $dto): void
    {
        $staff = $this->repository->get($dto->id);
        if (!$staff) {
            throw new StaffNotFoundException($dto->id);
        }
        $uploadedPath = null;
        if ($dto->image) {
            $uploadedPath = $this->handleProfileImage($dto->image);
        }

        $userId = $this->staffLoginService->handle($dto, $staff);

        $data = [
            'name'          => $dto->name,
            'user_id'       => $userId,
            'email'         => $dto->email,
            'phone_number'  => $dto->phone,
            'about'         => $dto->note,
            'profile_image' => $uploadedPath ?? $staff->profile_image,
            'locations'     => implode(',', $dto->locations ?? []),
            'profession'    => $dto->profession,
        ];

        $this->repository->update($dto->id, $data);

        $this->timesheetRepository->deleteByStaffId($dto->id);
    }

    /**
     * @throws StaffLimitExceededException
     * @throws StaffNotFoundException
     */
    public function duplicate(int $id): int
    {
        $this->checkAllowedStaffLimit();

        $staff = $this->repository->get($id);

        if (!$staff) {
            throw new StaffNotFoundException($id);
        }

        $newImage = $this->mediaService->copy((string) $staff->profile_image, 'Staff');

        $data = [
            'name'          => $staff->name . ' (2)',
            'email'         => $staff->email,
            'phone_number'  => $staff->phone_number,
            'about'         => $staff->about,
            'profile_image' => $newImage,
            'locations'     => $staff->locations,
            'profession'    => $staff->profession,
            'is_active'     => $staff->is_active,
        ];

        $newId = $this->repository->insert($data);

        $this->repository->duplicateServiceStaff($id, $newId);
        $this->repository->duplicateTimesheet($id, $newId);
        $this->repository->duplicateTranslations($id, $newId);
        $this->repository->duplicateData($id, $newId);

        return $newId;
    }

    /**
     * @throws StaffNotFoundException
     * @throws StaffValidationException
     */
    public function delete(array $ids, bool $allowWpDelete = false): array
    {
        $deletedIds = [];

        foreach ($ids as $id) {
            $staff = $this->repository->get($id);
            if (!$staff) {
                throw new StaffNotFoundException($id);
            }

            if ($this->appointmentRepository->hasAppointmentsByStaffId($id)) {
                throw new StaffValidationException(
                    bkntc__('This staff has active appointments. Please remove them first!')
                );
            }

            if (!empty($staff->user_id)) {
                $this->staffLoginService->handleDeletion((int)$staff->user_id, $allowWpDelete);
            }

            $this->relationService->deleteAllForStaff($id);

            $this->mediaService->deleteUploadedFile((string)$staff->profile_image, 'Staff');

            $this->repository->delete($id);

            $deletedIds[] = $id;
        }

        return [
            'message'     => bkntc__('Staff successfully deleted.'),
            'deleted_ids' => $deletedIds,
        ];
    }

    /**
     * Ensures that staff creation does not exceed plan limit.
     *
     * @throws StaffLimitExceededException
     */
    public function checkAllowedStaffLimit(): void
    {
        $allowedLimit = $this->capabilityService->getLimit('staff_allowed_max_number');
        if ($allowedLimit > -1 && $this->repository->count() >= $allowedLimit) {
            throw new StaffLimitExceededException($allowedLimit);
        }
    }

    /**
     * Toggles the visibility (active/inactive) of a staff member.
     *
     * @throws StaffNotFoundException
     */
    public function toggleVisibility(int $staffId): array
    {
        $staff = $this->repository->get($staffId);
        if (!$staff) {
            throw new StaffNotFoundException($staffId);
        }

        $newStatus = $staff->is_active ? 0 : 1;

        $this->repository->update($staffId, ['is_active' => $newStatus]);

        return [
            'staff_id'   => $staffId,
            'new_status' => $newStatus,
        ];
    }

    /**
     * Generates a list of available time slots for dropdowns and autocomplete fields.
     */
    public function getAvailableTimes(string $search = ''): array
    {
        $timeslotLength = Helper::getOption('timeslot_length', 5);
        $tEnd = Date::epoch('00:00:00', '+1 days');
        $timeCursor = Date::epoch('00:00:00');
        $data = [];

        while ($timeCursor <= $tEnd) {
            $timeId = Date::timeSQL($timeCursor);
            $timeText = Date::time($timeCursor);

            if ($timeCursor == $tEnd) {
                $timeText = '24:00';
                $timeId   = '24:00';
            }

            $timeCursor += $timeslotLength * 60;

            if (! empty($search) && strpos($timeText, $search) === false) {
                continue;
            }

            $data[] = [
                'id'   => $timeId,
                'text' => $timeText
            ];
        }

        return $data;
    }

    private function handleProfileImage(?array $image): ?string
    {
        if (!$image || empty($image['tmp_name'])) {
            return null;
        }

        $pathInfo  = pathinfo($image['name']);
        $extension = strtolower($pathInfo['extension'] ?? '');

        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            throw new \RuntimeException(bkntc__('Only JPG and PNG images are allowed!'));
        }

        $fileName = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        $target   = Helper::uploadedFile($fileName, 'Staff');

        move_uploaded_file($image['tmp_name'], $target);

        return $fileName;
    }

    public function get(int $id = 0): StaffGetResponse
    {
        $staff     = $id ? $this->repository->get($id) : null;
        $selectedServices = $id ? $this->serviceStaffRepository->getIdsByStaffId($id) : [];
        $timesheet     = $this->timesheetRepository->getForStaffOrDefault($id);

        $specialDays = $this->relationService->getSpecialDays($id);
        $holidaysArr = $this->relationService->getHolidays($id);
        $locations = $this->relationService->getLocations();
        $services = $this->relationService->getServices();

        $users       = $this->staffLoginService->getAllUsers();
        $defaultCountryCode = Helper::getOption('default_phone_country_code', '');

        $mapper = new StaffMapper();

        $response = new StaffGetResponse();
        $response->setId($id);

        if ($staff !== null) {
            $staffResponse = $mapper->toResponse($staff);
            $response->setStaff($staffResponse);
        } else {
            $response->setStaff(new StaffResponse());
        }

        $response->setSelectedServices($selectedServices);
        $response->setTimesheet($timesheet['schedule']);
        $response->setHasSpecificTimesheet($timesheet['hasSpecific']);
        $response->setSpecialDays($mapper->toSpecialDayListResponse($specialDays));
        $response->setHolidays(json_encode($holidaysArr));
        $response->setLocations($mapper->toSelectOptionResponseList($locations));
        $response->setServices($mapper->toSelectOptionResponseList($services));
        $response->setUsers($users);
        $response->setDefaultCountryCode($defaultCountryCode);

        return $response;
    }

    public function getStaffList(string $search = '', int $location = 0, int $service = 0): array
    {
        return $this->repository->getAll($search, $location, $service);
    }

    public function getStaffForSelect(string $search): array
    {
        $staff = $this->repository->getAll($search);

        $data   = [];

        foreach ($staff as $staffInf) {
            $data[] = [
                'id'				=>	(int)$staffInf['id'],
                'text'				=>	htmlspecialchars($staffInf['name'])
            ];
        }

        return $data;
    }
}
