<?php

namespace Buan\scenario\crm;

use Buan\ModelException;
use Ias\AjaxHelper;
use Ias\AwsApi\ApiHelper;
use Ias\Helper\Validator;
use Ias\Input;
use Ias\ModelManager;

class BidRegistrations
{
    /**
     * Validates $id is supplied and matches our id format, failing with an
     * ajaxFail call, and an appropriate message, if validation fails.
     *
     * @param string|null $id The id to validate.
     * @param string|null $idName The name of the id.
     *
     */
    protected function validateId(?string $id, ?string $idName)
    {
        if (!$id) {
            AjaxHelper::ajaxFail("Missing/blank {$idName} parameter", AjaxHelper::HTTP_BAD_REQUEST);
        }
        if (!Validator::isValidIdFormat($id)) {
            AjaxHelper::ajaxFail("Invalid format for {$idName}", AjaxHelper::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return void
     * @throws \Buan\Exception
     * @throws ModelException
     * @throws \Ias\Exception
     */
    public function propertiesBidRegistration(): void
    {
        AjaxHelper::verifyIsGetRequest();

        $userId = Input::get('user_id', null);
        $this->validateId($userId, 'user_id');

        $limit = Input::get('limit', '20');
        $offset = Input::get('offset', '0');

        $sql = "SELECT
            br.property_id, br.id AS `bid_registration_id`, br.status AS `registration_status`, br.date_approved AS `date_first_approved`
        FROM ias_property_bid_register br
        INNER JOIN ias_property p ON p.id = br.property_id
        INNER JOIN ias_user_customer uc ON uc.id = br.user_customer_id
        INNER JOIN ias_user u ON u.id = uc.user_id
        WHERE
            u.id = ? AND u.isactive = 1 AND u.is_verified = 1
            AND p.isactive = 1 AND p.isarchived = 0 AND p.status IN (?, ?, ?, ?, ?)
        GROUP BY br.property_id, br.date_created
        LIMIT $limit OFFSET $offset;
        ";

        $propertyIds = [];
        $additionalData = [];

        $allowedStatuses = array_merge(['preapproved', 'unsold', 'pam', 'sale', 'sold']);

        $rows = ModelManager::sqlQuery($sql, array_merge([$userId], $allowedStatuses))->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $propertyId = $row['property_id'];
            $propertyIds[] = $propertyId;

            $percent = PropertyBidRegisterManager::getBidRegInfo($userId, $propertyId, true);

            $additionalData[$propertyId] = [
                'bid_registration_id' => $row['bid_registration_id'],
                'status' => $row['registration_status'],
                'date_approved' => $row['date_first_approved'],
                'completion_percentage' => ceil($percent),
            ];
        }

        if (empty($propertyIds)) {
            ApiHelper::sendJson(['results' => []]);
        }

        $propertyCardResponse = PropertyCard::getResponse($propertyIds, true, $userId, $additionalData);

        $output = [
            'results' => $propertyCardResponse,
        ];

        ApiHelper::sendJson($output);
    }
}
