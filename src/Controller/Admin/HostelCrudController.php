<?php

namespace App\Controller\Admin;

use App\Entity\Hostel;
use App\Entity\RoomAmenities;
use App\Repository\CountrysRepository;
use App\Repository\CurrencyRepository;
use App\Repository\FederalStateRepository;
use App\Repository\RoomAmenitiesRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class HostelCrudController extends AbstractCrudController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var RoomAmenities
     */
    private $roomAmenities;
    /**
     * @var CountrysRepository
     */
    private $countrysRepository;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var FederalStateRepository
     */
    private $federalStateRepository;

    public static function getEntityFqcn(): string
    {
        return Hostel::class;
    }

    public function __construct(
        UserRepository $userRepository,
        RoomAmenitiesRepository $roomAmenities,
        CountrysRepository $countrysRepository,
        CurrencyRepository $currencyRepository,
        FederalStateRepository $federalStateRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roomAmenities = $roomAmenities;
        $this->buildRoomAmenitiesOptions();// todo remove
        $this->countrysRepository = $countrysRepository;
        $this->currencyRepository = $currencyRepository;
        $this->federalStateRepository = $federalStateRepository;
    }

    /**
     * Create a new hostel with
     * the id from the logged in user
     * a user cant have many hostel's
     *
     * @param string $entityFqcn
     * @return Hostel|mixed
     */
    public function createEntity(string $entityFqcn)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $hostel = new Hostel();
        $hostel->setUserId((int)$user->getId());

        return $hostel;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }


    public function configureFields(string $pageName): iterable
    {

        // id fields
        $id = IdField::new('id');
        $user_id = IdField::new('user_id');

        // data fields
        $hostel_name = TextField::new('hostel_name');

        $address = TextField::new('address', 'Straße');
        $address_sub = TextField::new('address_sub', 'Adress zusatz');
        $postcode = IntegerField::new('postcode', 'PLZ');
        $city = TextField::new('city', 'Stadt');

        /* Build the federal state dropdown field SH, NRW */
        $state = TextField::new('state', 'Bundesland')->setHelp(
            'In welchem Bundesland liegt Ihre Unterkunft'
        )
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions(
                [
                    'choices'  => [
                        $this->buildFederalStateOptions(),
                    ],
                    'group_by' => 'id',
                ]
            );

        $country = TextField::new('country', 'Land');// todo add dropdown
        $country_id = IntegerField::new('country_id'); // intern filed only
        $longitude = NumberField::new('longitude');
        $latitude = NumberField::new('latitude');
        $phone = TextField::new('phone', 'Festnezt');
        $mobile = TextField::new('mobile');
        $fax = TextField::new('fax');
        $web = UrlField::new('web');
        $email = EmailField::new('email');

        /* Build the currency dropdown field EUR, GBP */
        $currency = TextField::new('currency', 'Währung')->setHelp(
            'Die Währung in der Sie abrechnen'
        )
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions(
                [
                    'choices'  => [
                        $this->buildCurrencyOptions(),
                    ],
                    'group_by' => 'id',
                ]
            );

        $room_types = TextField::new('room_types');// todo dropdown array[]

        /* amenities choices array NO_SMOKING, W-LAN*/
        $amenities = CollectionField::new('amenities', 'Ausstattung')->setHelp(
            'Ausstattung die generell im Hostel verfügbar ist'
        )
            ->setEntryType(ChoiceType::class)
            ->setFormTypeOptions(
                [
                    'entry_options' => [
                        'choices'  => [
                            $this->buildRoomAmenitiesOptions(),
                        ],
                        'label'    => false,
                        'group_by' => 'id',
                    ],
                ]
            );

        $description = TextEditorField::new('description', 'Beschreibung');

        // api connection parameter for hostel availability import
        $api_key = TextField::new('api_key');// todo only display autogenarate UUid
        $hostel_availability_url = UrlField::new('hostel_availability_url');

        // Extra cost field only by admin editable
        $sort = TextField::new('sort'); //todo add only by admin over extra pay
        $startpage = BooleanField::new('startpage');
        $toplisting = BooleanField::new('toplisting');
        $top_placement_finished = DateTimeField::new('top_placement_finished');

        // Hostel on or offline switch
        $status = BooleanField::new('status');


        // output fields by page
        if (Crud::PAGE_INDEX === $pageName) {
            return [$user_id, $hostel_name, $address, $postcode, $city, $status];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user_id,
                $hostel_name,
                $address,
                $address_sub,
                $postcode,
                $city,
                $state,
                $country,
                $country_id,
                $longitude,
                $latitude,
                $phone,
                $mobile,
                $fax,
                $web,
                $email,
                $currency,
                $room_types,
                $amenities,
                $description,
                $hostel_availability_url,
                $sort,
                $startpage,
                $toplisting,
                $top_placement_finished,
                $status,
            ];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [
                $hostel_name,
                $address,
                $address_sub,
                $postcode,
                $city,
                $state,
                $country,
                $country_id,
                $longitude,
                $latitude,
                $phone,
                $mobile,
                $fax,
                $web,
                $email,
                $currency,
                $room_types,
                $amenities,
                $description,
                $api_key,
                $hostel_availability_url,
                $status,
            ];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [
                $hostel_name,
                $address,
                $address_sub,
                $postcode,
                $city,
                $state,
                $country,
                $country_id,
                $longitude,
                $latitude,
                $phone,
                $mobile,
                $fax,
                $web,
                $email,
                $currency,
                $room_types,
                $amenities,
                $description,
                $api_key,
                $hostel_availability_url,
                $status,
            ];
        }
    }


    ##########################################
    #
    # Helper function protected
    #
    ##########################################

    /**
     * Create the option array
     * @return array
     */
    protected function buildRoomAmenitiesOptions()
    {

        $options = [];

        // get from db
        $roomAmenities = $this->roomAmenities->getRoomAmenitiesWithDescription();

        // build option array
        foreach ($roomAmenities as $roomAmenity) {
            $options[$roomAmenity[0]->getName()] = $roomAmenity['name'];
        }

        return $options;
    }

    /**
     * Create the option array
     * @return array
     */
    protected function buildCurrencyOptions()
    {
        $options = [];

        $currencys = $this->currencyRepository->findBy(['status' => true]);

        foreach ($currencys as $currency) {
            $options[$currency->getCode()] = $currency->getName();
        }

        return $options;
    }

    /**
     * Create the option array
     * @return array
     */
    protected function buildFederalStateOptions()
    {
        $options = [];

        $federalStates = $this->federalStateRepository->findBy(['status' => true]);

        foreach ($federalStates as $federalState) {
            $options[$federalState->getName()] = $federalState->getCode();
        }

        return $options;
    }

}
