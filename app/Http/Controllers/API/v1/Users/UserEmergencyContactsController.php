<?php

namespace App\Http\Controllers\API\v1\Users;

use App\Http\Controllers\Controller;
use App\Libraries\Repositories\UserEmergencyContactRepositoryEloquent;
use App\Models\UserEmergencyContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserEmergencyContactsController extends Controller
{

    protected $userId;
    protected $userEmergencyContactRepository;

    /**
     * UserEmergencyContactsController constructor.
     * @param UserEmergencyContactRepositoryEloquent $userEmergencyContactRepository
     */
    public function __construct(UserEmergencyContactRepositoryEloquent $userEmergencyContactRepository)
    {
        $this->userId = Auth::id();
        $this->userEmergencyContactRepository = $userEmergencyContactRepository;
    }

    public function getContactsDetails()
    {
        $contacts = $this->getFirstRecordById();
        if (!!!isset($contacts)) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => "Contacts"]));
        return $this->sendSuccessResponse($contacts, __('validation.common.details_found', ['module' => "Contacts"]));
    }

    public function getFirstRecordById()
    {
        return $this->userEmergencyContactRepository->getDetailsByInput([
            'user_id' => $this->userId,
            'first' => true,
        ]);
    }

    public function saved(Request $request)
    {
        $input = $request->all();

        /** get first record */
        $contact = $this->getFirstRecordById();

        /** check model validation */
        $validation = UserEmergencyContact::validation($input, $contact->id ?? null);
        if (isset($validation) && $validation->errors()->count() > 0) {
            return $this->sendBadRequest(null, $validation->errors()->first());
        }

//        $validation = $this->requiredValidation(['contact_1'], $input);
//        if (isset($validation) && $validation['flag'] === false) return $this->sendBadRequest(null, $validation['message']);

        $contacts = $this->userEmergencyContactRepository->updateOrCreate(
            [
                'user_id' => $this->userId
            ],
            $input
        );
        return $this->sendSuccessResponse($contacts, __('validation.common.saved', ['module' => "Contacts"]));

    }

}
