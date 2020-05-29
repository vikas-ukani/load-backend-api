<?php /** @noinspection ALL */

namespace App\Http\Controllers\API\v1\Library;

use App\Libraries\Repositories\LogResistanceValidationsRepositoryEloquent;
use App\Libraries\Repositories\UsersRepositoryEloquent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogResistanceValidationsController extends Controller
{

    protected $moduleName = "Log Resistance Validations";

    protected $logResistanceValidationsRepository;

    /**
     * LogResistanceValidationsController constructor.
     * @param LogResistanceValidationsRepositoryEloquent $logResistanceValidationsRepository
     */
    public function __construct(LogResistanceValidationsRepositoryEloquent $logResistanceValidationsRepository)
    {
        $this->logResistanceValidationsRepository = $logResistanceValidationsRepository;
    }

    /**
     * @param Request $request
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function listOfLogResistanceValidations(Request $request)
    {
        $input = $request->all();

        $logResistanceValidations = $this->logResistanceValidationsRepository->getDetailsByInput($input);
        if (!!!isset($logResistanceValidations)) return $this->sendBadRequest(null, __('validation.common.details_not_found', ['module' => $this->moduleName]));

        return $this->sendSuccessResponse($logResistanceValidations, __('validation.common.details_found', ['module' => $this->moduleName]));
    }
}
