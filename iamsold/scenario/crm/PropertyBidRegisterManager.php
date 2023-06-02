<?php

namespace Buan\scenario\crm;

use Buan\Config;
use Buan\Database;
use Buan\ModelCollection;
use Buan\ModelException;
use Buan\UrlCommand;
use Ias\AuctionFee;
use Ias\ElectronicSignature;
use Ias\Email\AccountsAcceptTermsBidUpdateReimbursement;
use Ias\Email\CustomerBidRegisterExpired;
use Ias\Email\CustomerBidRegisterPropertyLive;
use Ias\Email\CustomerBidRegisterWithdrawn;
use Ias\Email\StaffBidRegisterReimburse;
use Ias\Environment;
use Ias\Event;
use Ias\Exception;
use Ias\Feature;
use Ias\FranchiseFacade;
use Ias\Helper\ErrorHandling;
use Ias\Helper\FileOperators;
use Ias\Helper\Strings;
use Ias\IasException;
use Ias\Model;
use Ias\ModelCriteria;
use Ias\ModelManager;
use Ias\PDFHelper;
use Ias\ProxyBidForm;
use Ias\PublicView;
use Ias\ReservationForm;
use Ias\Session;
use Ias\Tasks\Sales\MortgageReferral;
use Ias\Tasks\Sales\PendingBidRegistrationToReview;

class PropertyBidRegisterManager extends ModelManager
{
    /**
     * Loads existing Bid Registration (or creates new one) and returns the progress.
     * If $returnCompletion is passed, will return the completion percentage
     *
     * @param string $userId
     * @param string $propertyId
     * @param bool $returnCompletion
     * @param bool $disableExceptions
     * @param bool $createNewRegistrationIfNotExists
     * @param bool $applicantSeparateToBuyers
     * @param bool $isInternalRequest
     * @return array
     * @throws Exception
     * @throws ModelException
     * @throws \Buan\Exception
     * @throws \Exception
     */
    public static function getBidRegInfo(
        string $userId,
        string $propertyId,
        bool   $returnCompletion = false,
        bool   $disableExceptions = false,
        bool   $createNewRegistrationIfNotExists = false,
        bool   $applicantSeparateToBuyers = false,
        bool   $isInternalRequest = false
    ): array
    {
        // Does some queries to set variables used in the response...

        // Populate response object
        $response = [
            'bid_registration_id' => $bidRegisterId,
            'new_registration' => $newBidReg,
            'bid_registration_status' => $propertyBidRegister->status,
            'can_skip_solicitor' => $canSkipSolicitorStep,
            'has_terms_update' => !!$propertyBidRegister->has_terms_update,
            'show_request_quote' => !$property->isWithinScotland(),
            'personal_details' => [
                'status' => true,
                'data' => [],
            ],
            'additional_buyers' => [],
            'company_buyers' => [],
            'solicitor_details' => [
                'status' => !!$propertyBidRegister->solicitor_details_required,
                'data' => [],
            ],
            'requires_id' => [
                'status' => $requiresIdStatus,
                'data' => [],
            ],
            'requires_payment' => [
                'status' => false,
                'data' => [],
            ],
            'requires_online_document_signing' => [
                'status' => $requiresOnlineDocSigning,
                'data' => [],
            ],
            'agreed_terms_and_conditions' => [
                'status' => true,
                'data' => [],
            ],
        ];

        // Return the response, or a random number between 0 and 100 as to the completion state.
        return ($returnCompletion) ? rand(0, 100) : $response;
    }
}
