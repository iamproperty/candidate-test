<?php

namespace Buan\scenario\crm;

use AgencyManager;
use AgencyModel;
use Exception;
use Ias\Helper\Arrays;
use Ias\Helper\ErrorHandling;
use Ias\ModelCriteria;
use Ias\ModelManager;
use PDO;
use PropertyAuctionPackModel;
use PropertyImageManager;
use PropertyImageModel;
use PropertyManager;
use PropertyModel;
use PropertySpecificationManager;
use PropertyStylesManager;
use PropertyStylesModel;
use PropertyTypeManager;
use PropertyTypeModel;
use UserCustomerManager;
use UserCustomerModel;
use function Ias\AwsApi\formatCurrency;

class PropertyCard
{
    /**
     * @param array $propertyIds
     * @param bool $includePreapp
     * @param string|null $userId
     * @param array $additionalData
     * @return array
     */
    public static function getResponse(array $propertyIds, bool $includePreapp = false, ?string $userId = null, array $additionalData = []): array
    {
        // Produce a query to get the data for the response based on the params passed through.
        $searchQuery = '';
        $availableStatuses = [];

        $output = [];
        try {
            $records = ModelManager::sqlQuery($searchQuery, $availableStatuses)->fetchAll(PDO::FETCH_ASSOC);
            // begin loop
            foreach ($records as $row) {
                // don't show withdrawn
                if ($row['isWithdrawn'] == 1 && $row['withdrawal_enddate'] > 0 and $row['withdrawal_enddate'] < time()) {
                    continue;
                }
                $propertyId = $row['property_id'];

                $property = PropertyManager::fetch($propertyId);

                $bids = "SELECT id, bid FROM ias_property_bid WHERE property_id = ? AND withdrawn = 0 AND inactive = 0 AND isdeclined = 0";
                $bidOutput = ModelManager::sqlQuery($bids, [$propertyId])->fetchAll(PDO::FETCH_OBJ);

                $bids = [];
                $winningBidValue = 0;
                foreach ($bidOutput as $b) {
                    $bids[] = $b->bid;

                    if ($b->id == $property->winning_bid_id) {
                        $winningBidValue = $b->bid;
                    }
                }

                $highestBid = (!empty($bids)) ? max($bids) : 0;
                $currentPrice = ($row['status'] == PropertyModel::STATUS_SOLD && !empty($property->winning_bid_id)) ? $winningBidValue : max($row['start_price'], $highestBid, 0);
                $numberOfBids = in_array($row['status'], $saleOrSoldArray) ? count($bids) : 0;

                $specRecords = PropertySpecificationManager::getSpecificationData($propertyId);

                $styleNameItem = $typeNameItem = '';
                $bedrooms = $receptions = $bathrooms = 0;

                if ($specRecords) {
                    $bedrooms = $specRecords[0]->bedrooms;
                    $receptions = $specRecords[0]->reception_rooms;
                    $bathrooms = $specRecords[0]->bathrooms;

                    /** @var PropertyStylesModel $styleName */
                    $styleName = PropertyStylesManager::fetch($specRecords[0]->property_styles_id, false, 'style_name');
                    if ($styleName) {
                        $styleNameItem = $styleName->style_name;
                    }

                    /** @var PropertyTypeModel $typeName */
                    $typeName = PropertyTypeManager::fetch($specRecords[0]->property_type_id, false, 'type_name');
                    if ($typeName) {
                        $typeNameItem = $typeName->type_name;
                    }
                }

                if (empty($row['tenure'])) {
                    $row['tenure'] = 'unknown';
                }

                $address = [
                    'thoroughfare' => $row['thoroughfare'],
                    'dependent_thoroughfare' => $row['dependent_thoroughfare'],
                    'dependent_locality' => $row['dependent_locality'],
                    'double_dependent_locality' => $row['double_dependent_locality'],
                    'post_town' => $row['post_town'],
                    'county' => $row['county'],
                    'post_code' => $row['post_code'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                ];
                $specification = [
                    'bedrooms' => $bedrooms,
                    'reception_rooms' => $receptions,
                    'bathrooms' => $bathrooms,
                    'property_style' => $styleNameItem,
                    'property_type' => $typeNameItem,
                    'tenure' => PropertyAuctionPackModel::TENURES[$row['tenure']],
                ];
                $auctionTypes = [
                    0 => 'In Room Auction',
                    1 => 'Online Auction',
                    2 => 'Call us to Bid Auction',
                ];

                if (
                    $row['status'] == PropertyModel::STATUS_SOLD
                    || (
                        $row['status'] == PropertyModel::STATUS_SALE
                        && $row['dateauctionend'] < time()
                        && $row['dateauctionend'] > 0
                        && $row['dateauctionstart'] < time()
                        && $row['dateauctionstart'] > 0
                        && $highestBid >= $row['reserve_price']
                    )
                ) {
                    $labelVal = 'Sold for';
                } else {
                    $labelVal = 'Current bid';
                    if (
                        $numberOfBids == 0
                        || ($numberOfBids > 0 && $highestBid < $row['start_price'])
                    ) {
                        $labelVal = 'Starting bid';
                    }
                }

                $auction_data = [
                    'current_price' => (int)$currentPrice,
                    'auction_start' => $row['dateauctionstart'],
                    'auction_end' => $row['dateauctionend'],
                    'auction_type' => $auctionTypes[$row['isonline']],
                    'start_price' => (int)$row['start_price'],
                    'allow_online_bidding' => 'true',
                    'label' => $labelVal,
                ];

                /** @var AgencyModel $agency */
                $agency = AgencyManager::loadById($row['agency_id']);

                $cardLogo = $agency->getAgencyLogo(true, true, true, null, false);
                $nonCardLogo = $agency->getAgencyLogo(false, true, true);

                $agency_details = [
                    'agency_name' => $row['agency_name'],
                    'agency_logo' => $cardLogo ?: $nonCardLogo,
                ];

                if (!empty($row['standard_image_id'])) {
                    /** @var PropertyImageModel $img */
                    $img = PropertyImageManager::loadById($row['standard_image_id']);
                }

                $propertyMedia = [
                    'count_images' => (int)$row['count_images'],
                    'standard_image_url' => isset($img) ? $img->getUrl() : '',
                ];

                $feeLine = $row['fee_percentage'] . '% including VAT, of the final agreed sale price, to a minimum of ' . formatCurrency($row['minimum_fee']);
                $fees = [
                    'fee_type' => $row['fee_type'],
                    'text_line' => $feeLine,
                    'buyer_information_pack_fee' => $property->getBuyerInfoPackFee(),
                    'id_checks_fee' => '10',
                    'fees_and_charges_explained' => 'TBC',
                ];
                // if user_id passed, include any user-specific information
                $userSpecific = [];
                if (in_array($propertyId, $savedPropertyIds)) {
                    $userSpecific['on_saved_list'] = 1;
                }

                $addData = [];
                if (!empty($additionalData) && array_key_exists($propertyId, $additionalData)) {
                    $addData = $additionalData[$propertyId];
                }

                $bedLine = $bedrooms > 0 ? $bedrooms . ' bed ' : '';

                // build the final formatted output
                $output[] = [
                    'ias_id' => $propertyId,
                    'status' => strtolower($row['status']),
                    'tenure' => PropertyAuctionPackModel::TENURES[$row['tenure']],
                    'address' => $address,
                    'property_card_title' => $bedLine . ($typeNameItem ?: 'property'),
                    'specification' => $specification,
                    'auction_data' => $auction_data,
                    'agency_details' => $agency_details,
                    'property_media' => $propertyMedia,
                    'fees' => $fees,
                    'user_specific' => $userSpecific,
                    'additional_data' => $addData,
                ];
            } // end loop
        } catch (Exception $e) {
            ErrorHandling::handleException($e, true);
            return ['error' => $e->getMessage()];
        }

        return $output;
    }
}
