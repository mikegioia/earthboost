<?php

namespace App\Libraries;

/**
 * Calculates the emissions in metric tonnes and dollar
 * value for a set of metrics.
 */
class Calculator
{
    // kW/h per server
    const KWH_SERVER = 0.25;
    // MT per mile
    const MT_BUS = 0.000055;
    // MT per meat eating day
    const MT_MEAT_DAY = 0.007;
    // MT per gallon
    const MT_PROPANE = 0.00574;
    // MT per hotel night
    const MT_HOTEL_DAY = 0.0168;
    // MT per mile
    const MT_GASOLINE = 0.000355;
    // MT per pound
    const MT_WASTE = 0.000453592;
    // MT per therm
    const MT_NATURAL_GAS = 0.005;
    // MT per gallon oil
    const MT_HEATING_OIL = 0.01015;
    // MT per mile
    const MT_RAIL_TRAIN = 0.000169;
    const MT_RAIL_SUBWAY = 0.000121;
    // MT per kW/h
    const MT_ELECTRICITY = 0.0005925;
    // MT per mile
    const MT_FLIGHT_LONG = 0.000167;
    const MT_FLIGHT_SHORT = 0.000251;
    const MT_FLIGHT_MEDIUM = 0.000143;
    // MT per square foot
    const MT_OFFICE_SQFT = 0.00547809;
    // Radiative forcing multiplier
    const MULT_RADIATIVE_FORCING = 2.7;

    /**
     * Used externally when constructing.
     */
    const WASTE = 'waste';
    const SERVERS = 'servers';
    const BUS_MILES = 'bus_miles';
    const MEAT_DAYS = 'meat_days';
    const CAR_MILES = 'car_miles';
    const OIL_GALLONS = 'oil_gallons';
    const TRAIN_MILES = 'train_miles';
    const OFFICE_SQFT = 'office_sqft';
    const HOTEL_DAYS = 'hotel_days';
    const SUBWAY_MILES = 'subway_miles';
    const PROPANE_GALLONS = 'propane_gallons';
    const ELECTRICITY_KWH = 'electricity_kwh';
    const FLIGHT_MILES_LONG = 'flight_miles_long';
    const NATURAL_GAS_THERMS = 'natural_gas_therms';
    const FLIGHT_MILES_SHORT = 'flight_miles_short';
    const FLIGHT_MILES_MEDIUM = 'flight_miles_medium';

    /**
     * User storage keys from the questions asked. These need to
     * be mapped to our internal constants.
     */
    const USER_WASTE = 'WA';
    const USER_HOME_AREA = 'HA';
    const USER_MEAT_DAYS = 'MD';
    const USER_BUSES_LONG = 'BL';
    const USER_CARS_MILES = 'CM';
    const USER_ENERGY_GAS = 'EG';
    const USER_ENERGY_OIL = 'EO';
    const USER_HOTEL_DAYS = 'HD';
    const USER_TRAINS_LONG = 'TL';
    const USER_BUSES_SHORT = 'BS';
    const USER_WEB_SERVERS = 'WS';
    const USER_OFFICE_AREA = 'OA';
    const USER_HOME_PEOPLE = 'HP';
    const USER_BUSES_MEDIUM = 'BM';
    const USER_FLIGHTS_LONG = 'FL';
    const USER_TRAINS_SHORT = 'TS';
    const USER_SUBWAYS_LONG = 'SL';
    const USER_ENERGY_POWER = 'EP';
    const USER_TRAINS_MEDIUM = 'TM';
    const USER_FLIGHTS_SHORT = 'FS';
    const USER_SUBWAYS_SHORT = 'SS';
    const USER_FLIGHTS_MEDIUM = 'FM';
    const USER_ENERGY_PROPANE = 'ER';

    /**
     * Used for calculating. If the type is USER then we need to
     * apply a conversion to get the data into the proper format.
     */
    const TYPE_RAW = 'raw';
    const TYPE_USER = 'user';

    /**
     * Return types.
     */
    const RETURN_STATS = 'stats';
    const RETURN_METRIC_TONS = 'metric_tons';

    /**
     * Carbon prices from vendors.
     */
    const PRICE_COTAP = 9.8;

    /**
     * Emissions data storage.
     */
    private $emissions = [];

    /**
     * Whether to include radiative forcing.
     */
    private $useRadiativeForcing = TRUE;

    public function __construct(
        array $emissions = [],
        $useRadiativeForcing = TRUE,
        $emissionsType = self::TYPE_USER )
    {
        $this->emissions = $emissions;
        $this->useRadiativeForcing = $useRadiativeForcing;

        if ( $emissionsType === self::TYPE_USER ) {
            $this->emissions = $this->convertUserEmissions( $emissions );
        }
    }

    public function getEmissions()
    {
        return $this->emissions;
    }

    public function getEmissionsData()
    {
        return $this->calculate( self::RETURN_STATS );
    }

    /**
     * Produces a number in metric tons.
     * @param $returnType Which type of value to return
     * @return float
     */
    public function calculate( $returnType = self::RETURN_METRIC_TONS )
    {
        $total = 0;
        $mtStats = [];
        $radiativeForcing = ( $this->useRadiativeForcing )
            ? self::MULT_RADIATIVE_FORCING
            : 1;

        foreach ( $this->emissions as $key => $value ) {
            switch ( $key ) {
                case self::WASTE:
                    $mt = $value * self::MT_WASTE;
                    break;
                case self::SERVERS:
                    $mt = $value * self::KWH_SERVER * self::MT_ELECTRICITY * 24 * 365;
                    break;
                case self::BUS_MILES:
                    $mt = $value * self::MT_BUS;
                    break;
                case self::MEAT_DAYS:
                    $mt = $value * self::MT_MEAT_DAY;
                    break;
                case self::CAR_MILES:
                    $mt = $value * self::MT_GASOLINE;
                    break;
                case self::HOTEL_DAYS:
                    $mt = $value * self::MT_HOTEL_DAY;
                    break;
                case self::OIL_GALLONS:
                    $mt = $value * self::MT_HEATING_OIL;
                    break;
                case self::TRAIN_MILES:
                    $mt = $value * self::MT_RAIL_TRAIN;
                    break;
                case self::OFFICE_SQFT:
                    $mt = $value * self::MT_OFFICE_SQFT;
                    break;
                case self::SUBWAY_MILES:
                    $mt = $value * self::MT_RAIL_SUBWAY;
                    break;
                case self::PROPANE_GALLONS:
                    $mt = $value * self::MT_PROPANE;
                    break;
                case self::ELECTRICITY_KWH:
                    $mt = $value * self::MT_ELECTRICITY;
                    break;
                case self::FLIGHT_MILES_LONG:
                    $mt = $value * self::MT_FLIGHT_LONG * $radiativeForcing;
                    break;
                case self::NATURAL_GAS_THERMS:
                    $mt = $value * self::MT_NATURAL_GAS;
                    break;
                case self::FLIGHT_MILES_SHORT:
                    $mt = $value * self::MT_FLIGHT_SHORT * $radiativeForcing;
                    break;
                case self::FLIGHT_MILES_MEDIUM:
                    $mt = $value * self::MT_FLIGHT_MEDIUM * $radiativeForcing;
                    break;
                default:
                    $mt = 0;
            }

            $total += $mt;

            if ( $mt ) {
                $mtStats[ $key ] = (object) [
                    'mt' => $mt,
                    'val' => $value
                ];
            }
        } // foreach

        switch ( $returnType ) {
            case self::RETURN_STATS:
                return $mtStats;
            case self::RETURN_METRIC_TONS:
            default:
                return $total;
        }
    }

    /**
     * Produces a price for the emissions.
     * @param $mt Optional metric tons of emissions. If not specified
     *   then this will calculate the emissions.
     * @param $vendorPrice Optional, defaults to COTAP
     * @return float
     */
    public function price( $mt = NULL, $vendorPrice = self::PRICE_COTAP )
    {
        return ( $mt ?: $this->calculate() ) * $vendorPrice;
    }

    /**
     * Converts an array of user emission data to the format we need
     * for calculating the metric tons.
     * @param array $emissions
     * @return array
     */
    public function convertUserEmissions( $emissions )
    {
        // Set up default values for everything
        $raw = [
            self::WASTE => 0,
            self::SERVERS => 0,
            self::BUS_MILES => 0,
            self::MEAT_DAYS => 0,
            self::CAR_MILES => 0,
            self::OIL_GALLONS => 0,
            self::TRAIN_MILES => 0,
            self::OFFICE_SQFT => 0,
            self::HOTEL_DAYS => 0,
            self::SUBWAY_MILES => 0,
            self::PROPANE_GALLONS => 0,
            self::ELECTRICITY_KWH => 0,
            self::FLIGHT_MILES_LONG => 0,
            self::NATURAL_GAS_THERMS => 0,
            self::FLIGHT_MILES_SHORT => 0,
            self::FLIGHT_MILES_MEDIUM => 0
        ];

        foreach ( $emissions as $e ) {
            $key = $e->type_id;
            $value = $e->value;

            switch ( $key ) {
                case self::USER_WASTE:
                    $raw[ self::WASTE ] += $value;
                    break;
                case self::USER_MEAT_DAYS:
                    $raw[ self::MEAT_DAYS ] += $value;
                    break;
                case self::USER_CARS_MILES:
                    $raw[ self::CAR_MILES ] += $value;
                    break;
                case self::USER_ENERGY_GAS:
                    $raw[ self::NATURAL_GAS_THERMS ] += $value;
                    break;
                case self::USER_ENERGY_OIL:
                    $raw[ self::OIL_GALLONS ] += $value;
                    break;
                case self::USER_HOTEL_DAYS:
                    $raw[ self::HOTEL_DAYS ] += $value;
                    break;
                case self::USER_BUSES_LONG:
                    $raw[ self::BUS_MILES ] += $value * 1000;
                    break;
                case self::USER_TRAINS_LONG:
                    $raw[ self::TRAIN_MILES ] += $value * 1000;
                    break;
                case self::USER_BUSES_SHORT:
                    $raw[ self::BUS_MILES ] += $value * 200;
                    break;
                case self::USER_WEB_SERVERS:
                    $raw[ self::SERVERS ] += $value;
                    break;
                case self::USER_HOME_AREA:
                case self::USER_OFFICE_AREA:
                    $raw[ self::OFFICE_SQFT ] += $value;
                    break;
                case self::USER_HOME_PEOPLE:
                    // Not used right now
                    break;
                case self::USER_BUSES_MEDIUM:
                    $raw[ self::BUS_MILES ] += $value * 500;
                    break;
                // Assumes a trip of 3,000 miles
                case self::USER_FLIGHTS_LONG:
                    $raw[ self::FLIGHT_MILES_LONG ] += $value * 6000;
                    break;
                case self::USER_TRAINS_SHORT:
                    $raw[ self::TRAIN_MILES ] += $value * 200;
                    break;
                // Assumes 5 mile trip
                case self::USER_SUBWAYS_LONG:
                    $raw[ self::SUBWAY_MILES ] += $value * 5;
                    break;
                case self::USER_ENERGY_POWER:
                    $raw[ self::ELECTRICITY_KWH ] += $value;
                    break;
                // Assumes a trip of 300 miles
                case self::USER_FLIGHTS_SHORT:
                    $raw[ self::FLIGHT_MILES_SHORT ] += $value * 600;
                    break;
                case self::USER_TRAINS_MEDIUM:
                    $raw[ self::TRAIN_MILES ] += $value * 500;
                    break;
                // Assumes 1 mile trip
                case self::USER_SUBWAYS_SHORT:
                    $raw[ self::SUBWAY_MILES ] += $value;
                    break;
                case self::USER_ENERGY_PROPANE:
                    $raw[ self::PROPANE_GALLONS ] += $value;
                    break;
                // Assumes a trip of 1,800 miles
                case self::USER_FLIGHTS_MEDIUM:
                    $raw[ self::FLIGHT_MILES_MEDIUM ] += $value * 3600;
                    break;
            } // switch
        } // foreach

        return $raw;
    }
}