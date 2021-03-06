<?php

namespace App\Repository;

use App\Entity\Hostel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Hostel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hostel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hostel[]    findAll()
 * @method Hostel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostelRepository extends ServiceEntityRepository
{
    private $regions_name;


    /**
     * @var RegionsRepository
     */
    private $regionsRepository;
    /**
     * @var mixed
     */
    private $maxDistanceToSee;
    private $maxPrice;
    private $minPrice;

    public function __construct(ManagerRegistry $registry, RegionsRepository $regionsRepository)
    {
        parent::__construct($registry, Hostel::class);

        $this->regionsRepository = $regionsRepository;
        $this->maxDistanceToSee = $this->buildMaxDistanceToSee();
        $this->buildPriceRange();
    }

    ##########################################
    #
    # Helper function For SearchHostelType
    #
    ##########################################

    /**
     * Build the maximal distance to the see for
     * the range slider search form filter
     *
     * @return mixed
     */
    protected function buildMaxDistanceToSee()
    {
        $qb = $this->createQueryBuilder('mds');

        $qb
            ->select('mds.distance_to_see')
            ->where('mds.status = 1')
            ->orderBy('mds.distance_to_see', 'DESC');

        $max = $qb
            ->getQuery()
            ->getResult();

        return $max[0]['distance_to_see'];
    }

    /**
     * Build the maximal price for the
     * range slider search form filter
     *
     * @return mixed
     */
    protected function buildPriceRange()
    {
        $qb = $this->createQueryBuilder('mp');

        $qb
            ->select('rt.final_rate')
            ->from('App:RoomTypes', 'rt')
            /*->where('mp.status = 1')*/
            ->orderBy('rt.final_rate', 'DESC');

        $max = $qb
            ->getQuery()
            ->getResult();

        $this->maxPrice = round($max[0]['final_rate'], 0, PHP_ROUND_HALF_UP) / 100;
        $this->minPrice = round(array_key_last($max)['final_rate'], 0, PHP_ROUND_HALF_DOWN) / 100;

        return true;
    }

    ##########################################
    #
    # QueryBuilder
    #
    ##########################################

    /**
     * Build the QueryBuilder with the search
     * form values and ranges $filter for
     * the hoste view search page
     *
     * @param array|null $filter
     * @return int|mixed|string
     * @throws \Exception
     */
    public function findHostelsWithFilter(?array $filter)
    {
        $qb = $this->createQueryBuilder('h');

        ###################
        #
        #   Dropdown filter
        #
        ###################
        // add the regions filter works by postcode
        if ($filter['regions']) {
            $plz = $this->regionsRepository->findOneBy(['regions_id' => $filter['regions']]);

            if ($plz) {
                // set the regions name for heading the search page
                $this->setRegionsName($plz->getName());

                $qb
                    ->andWhere('h.postcode = :plz')
                    ->setParameter('plz', $plz->getZipcode());
            } else {
                throw new \Exception('This region doesnt exist check your regions table.');
            }
        }

        // add the hostel type filter ['hostel_types'] => 64
        if ($filter['hostel_types']) {
            $qb
                ->leftJoin(
                    'App\Entity\AmenitiesTypes',
                    'at',
                    'WITH',
                    'h.hostel_type = at.name'
                )
                ->andWhere('at.amenities_id = :id')
                ->setParameter('id', $filter['hostel_types']);
        }

        ###################
        #
        #   Range Slider filter
        #
        ###################
        // add the query for person filter
        if ($filter['quantity_person']) {
            $qb
                ->leftJoin(
                    'App\Entity\RoomTypes',
                    'rt',
                    'WITH',
                    'h.id = rt.hostel_id'
                )
                ->andWhere('(rt.unit_occupancy * rt.number_of_units) >= :quantity')
                ->setParameter('quantity', $filter['quantity_person']);
        }

        // add the query for the price range filter
        if ($filter['price_range']) {
            $price_range = explode(';', $filter['price_range'], 2);
            $price_lowest = $price_range[0] * 100;
            $price_highest = $price_range[1] * 100;

            $qb
                ->leftJoin(
                    'App\Entity\RoomTypes',
                    'fr',
                    'WITH',
                    'h.id = fr.hostel_id'
                )
                ->andWhere('fr.final_rate >= :low AND fr.final_rate <= :hig')
                ->setParameter('low', $price_lowest)
                ->setParameter('hig', $price_highest);
        }

        // add query for the distance to see filter
        if ($filter['see_distance']) {
            // split request
            $see_distance = explode(';', $filter['see_distance'], 2);
            // cheaper than 1 is not supported by form type
            $distance_lowest = $see_distance[0];
            if ($distance_lowest == '1') {
                $distance_lowest = '0.1';
            }

            $distance_highest = $see_distance[1];

            $qb
                ->andWhere('h.distance_to_see >= :dlow AND h.distance_to_see <= :dhig')
                ->setParameter('dlow', $distance_lowest)
                ->setParameter('dhig', $distance_highest);
        }

        ###################
        #
        #   Checkbox filter
        #
        ###################
        //add query filter for isHandicappedAccessible
        if (isset($filter['handicap'])) {
            $qb
                ->leftJoin(
                    'App\Entity\RoomTypes',
                    'hc',
                    'WITH',
                    'h.id = hc.hostel_id'
                )
                ->andWhere('hc.isHandicappedAccessible = 1');
        }

        // add bread service query filter
        if (isset($filter['bread_service'])) {
            $qb
                ->andWhere("JSON_SEARCH(h.amenities, 'one', :service) IS NOT NULL ")
                ->setParameter('service', 'bread_service');
        }

        // add meal code filter query
        if (isset($filter['half_board'])) {
            $qb
                ->leftJoin(
                    'App\Entity\RoomTypes',
                    'hb',
                    'WITH',
                    'h.id = hb.hostel_id'
                )
                ->andWhere('hb.meal_code = :code')
                ->setParameter('code', 'HB');
        }

        // add breakfast filter query
        if (isset($filter['breakfast'])) {
            $qb
                ->leftJoin(
                    'App\Entity\RoomTypes',
                    'bi',
                    'WITH',
                    'h.id = bi.hostel_id'
                )
                ->andWhere('bi.breakfast_included = 1');
        }

        // getQuery
        return $qb
            ->orderBy('h.sort', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all the hostels for the Start Page Listing
     * with
     *
     * @param int $limit
     * @return int|mixed|string
     */
    public function findStartPageHostels(int $limit = 3)
    {
        $qb = $this->createQueryBuilder('hsp')
            ->where('hsp.status = 1')
            ->andWhere('hsp.startpage = 1')
            ->andWhere('hsp.top_placement_finished >= :time')
            ->setParameter('time', new \DateTime('now'))
            ->addOrderBy('hsp.sort', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the hostels for the Top Listing in Hostel View Controller
     * @return int|mixed|string
     */
    public function findTopListingHostels()
    {
        $qb = $this->createQueryBuilder('tlh')
            ->where('tlh.status = 1')
            ->andWhere('tlh.toplisting = 1')
            ->andWhere('tlh.top_placement_finished >= :time')
            ->setParameter('time', new \DateTime('now'))
            ->addOrderBy('tlh.sort', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all hostel with the $id_array of hostel ids
     *
     * @param array $id_array
     * @return int|mixed|string
     */
    public function findAllHostelWithId(array $id_array)
    {

        $qb = $this->createQueryBuilder('hn');

        $i = 1;
        foreach ($id_array as $id) {
            if ($i == 1) {
                $qb
                    ->where("hn.id = :id$i")
                    ->setParameter("id$i", $id);
            } else {
                $qb
                    ->orWhere("hn.id = :id$i")
                    ->setParameter("id$i", $id);
            }

            $i++;
        }

        $qb->andWhere('hn.status = 1');

        return $qb
            ->getQuery()
            ->getResult();
    }

    ##########################################
    #
    # Getter and Setter
    #
    ##########################################

    /**
     * @return mixed
     */
    public function getRegionsName()
    {
        return $this->regions_name;
    }

    /**
     * @param mixed $regions_name
     * @return HostelRepository
     */
    public function setRegionsName($regions_name)
    {
        $this->regions_name = $regions_name;

        return $this;
    }

    /**
     * Return the maximal distance to the see for
     * the range slider search form filter
     *
     * @return mixed
     */
    public function getMaxDistanceToSee()
    {
        return $this->maxDistanceToSee;
    }

    /**
     * Return the maximal price for the
     * range slider search form filter
     *
     * @return mixed
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * Return the miniimal price for the
     * range slider search form filter
     *
     * @return mixed
     */
    public function getMinPrice()
    {
        return $this->minPrice;
    }


    /**
     * Return the hostel with room types
     * for the detail page
     *
     * @param $id
     * @return int|mixed|string
     */
    public function findOneByIdJoinedToRoomTypes($id)
    {
        $qb = $this->createQueryBuilder('h')
           ->select('h AS hostel');

        $qb
            ->where('h.id = :id');


        $qb
            ->innerJoin(
                'App\Entity\RoomTypes',
                'rt',
                'WITH',
                'h.id = rt.hostel_id'
            )
            ->orderBy('rt.final_rate', 'ASC')
            ->addSelect('rt AS rooms');

        $qb
            ->innerJoin(
                'App\Entity\User',
                'u',
                'WITH',
                'h.user_id = u.id'
            )
            ->addSelect('u AS user');
        

        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }
}
