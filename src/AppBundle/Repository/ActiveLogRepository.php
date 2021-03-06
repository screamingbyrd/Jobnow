<?php

namespace AppBundle\Repository;

/**
 * ActiveLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ActiveLogRepository extends \Doctrine\ORM\EntityRepository
{
    public function selectCurrentLog($offerId, $slotId = null, $addToSlot = null){
        $query = $this->createQueryBuilder('a');
        $query->andWhere('a.offerId = :offerId and a.startDate <= CURRENT_TIMESTAMP()')
            ->setParameter('offerId', $offerId);

        if(isset($slotId)){
            $query->andWhere('a.slotId = :slotId')
                ->setParameter('slotId', $slotId);
        }else{
            $query->andWhere('a.slotId is NULL');
        }

        if(!isset($addToSlot) || !$addToSlot){
            $query->andWhere('a.endDate >= CURRENT_TIMESTAMP()');
        }else{
            $query->andWhere('a.endDate is NULL');
        }

        return $query->getQuery()->getResult();
    }

    public function countActiveBetween($startDate, $endDate){

        return $this->getEntityManager()
            ->createQuery(
                'select count(distinct al.offerId) as total
                    from AppBundle:activeLog al
                    where al.startDate <= :startDate and al.endDate >= :endDate or
                    al.startDate <= :startDate and al.endDate is NULL or
                    (month(al.startDate) = month(:startDate) and year(al.startDate) = year(:startDate)) or
                    (month(al.endDate) = month(:endDate) and year(al.endDate) = year(:endDate))'
            )->setParameter('startDate',$startDate)->setParameter('endDate',$endDate)->execute();
    }
}
