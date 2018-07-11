<?php
/**
 * Created by PhpStorm.
 * User: Altea IT
 * Date: 19/06/2018
 * Time: 08:33
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationController extends Controller
{

    public function createAction(Request $request)
    {
        $session = $request->getSession();
        $type = $request->get('type');
        $elementId = $request->get('id');

        $user = $this->getUser();
        if(!isset($user) || !in_array('ROLE_CANDIDATE', $user->getRoles())){
            return $this->redirectToRoute('create_candidate');
        }

        $candidateRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Candidate')
        ;
        $candidate = $candidateRepository->findOneBy(array('user' => $user->getId()));

        $notificationRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Notification')
        ;
        $notification = $notificationRepository->findOneBy(array(
            'candidate' => $candidate,
            'typeNotification' => $type,
            'elementId' => $elementId
        ));

        if(isset($notification) && !empty($notification)){
            $translated = $this->get('translator')->trans('notification.already');
            $session->getFlashBag()->add('danger', $translated);
            return $this->redirectToRoute('dashboard_candidate');
        }

        $session = $request->getSession();

        $notification = new Notification();

        $em = $this->getDoctrine()->getManager();

        $notification->setCandidate($candidate);

        $now =  new \DateTime();
        $notification->setDate($now);
        $notification->setTypeNotification($type);
        $notification->setElementId($elementId);

        $em->persist($notification);
        $em->flush();

        $translated = $this->get('translator')->trans('notification.created');
        $session->getFlashBag()->add('info', $translated);

        $url = $this->generateUrl('dashboard_candidate');

        return $this->redirect($url.'#alerts');

    }

    public function createSearchAction(Request $request)
    {
        $session = $request->getSession();
        $type = $request->get('type');
        $elementId = $request->get('id');
        $mail = $request->get('mail');

        $session = $request->getSession();

        $notification = new Notification();

        $em = $this->getDoctrine()->getManager();

        $notification->setCandidate(null);

        $now =  new \DateTime();
        $notification->setDate($now);
        $notification->setTypeNotification($type);
        $notification->setElementId($elementId);
        $notification->setMail($mail);

        $em->persist($notification);
        $em->flush();

        $mailer = $this->container->get('swiftmailer.mailer');

        $message = (new \Swift_Message('A new alert email has been created'))
            ->setFrom('jobnowlu@noreply.lu')
            ->setTo($mail)
            ->setBody(
                $this->renderView(
                    'AppBundle:Emails:notificationCreated.html.twig',
                    array()
                ),
                'text/html'
            )
        ;

        $mailer->send($message);

        $translated = $this->get('translator')->trans('notification.created');
        $session->getFlashBag()->add('info', $translated);

        $url = $this->generateUrl('search_page_offer');

        return $this->redirect($url.'#alerts');

    }

    public function deleteAction(Request $request, $notificationId){

        $session = $request->getSession();
        $user = $this->getUser();

        if(!isset($user) || !in_array('ROLE_CANDIDATE', $user->getRoles())){
            return $this->redirectToRoute('create_candidate');
        }

        $candidateRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Candidate')
        ;
        $candidate = $candidateRepository->findOneBy(array('user' => $user->getId()));

        $notificationRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Notification')
        ;
        $notification = $notificationRepository->findOneBy(array('id' => $notificationId));

        if(!isset($notification) || $candidate != $notification->getCandidate()){
            return $this->redirectToRoute('create_candidate');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($notification);
        $em->flush();

        $translated = $this->get('translator')->trans('notification.deleted');

        $session->getFlashBag()->add('info', $translated);

        return $this->redirectToRoute('dashboard_candidate');
    }

    //@TODO put in CRON
    public function sendAction(Request $request){

        $session = $request->getSession();

        $now =  new \DateTime();

        $notificationRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Notification')
        ;
        $offerRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Offer')
        ;
        $candidateRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Candidate')
        ;
        $employerRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Employer')
        ;
        $tagRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Tag')
        ;
        $notifications = $notificationRepository->findAll();
        $em = $this->getDoctrine()->getManager();

        foreach ($notifications as $notification){
            if($notification->getType != 'search'){
                $offers = $offerRepository->getNotificationOffers($notification);

                if(!empty($offers)){
                    $candidate = $candidateRepository->findOneBy(array('id' => $notification->getCandidate()));

                    if($notification->getTypeNotification() == 'employer'){
                        $employer = $employerRepository->findOneBy(array('id' => $notification->getElementId()));
                        $subject = $employer->getName();
                    }elseif ($notification->getTypeNotification() == 'tag'){
                        $tag = $tagRepository->findOneBy(array('id' => $notification->getElementId()));
                        $subject = $tag->getName();
                    }
                    $mail = $candidate->getUser()->getEmail();
                    $mailer = $this->container->get('swiftmailer.mailer');

                    $message = (new \Swift_Message('New offers could interest you'))
                        ->setFrom('jobnowlu@noreply.lu')
                        ->setTo($mail)
                        ->setBody(
                            $this->renderView(
                                'AppBundle:Emails:notification.html.twig',
                                array('offers' => $offers,
                                    'subject' => $translated = $this->get('translator')->trans($subject),
                                    'id' =>$notification->getId()
                                )
                            ),
                            'text/html'
                        )
                    ;

                    $mailer->send($message);
                }

                $notification->setDate($now);
                $em->merge($notification);
            }
        }

        $em->flush();

        return new Response();
    }

    //@TODO put in CRON
    public function sendSearchAction(Request $request){

        $session = $request->getSession();

        $now =  new \DateTime();

        $notificationRepository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Notification')
        ;
        $notifications = $notificationRepository->findBy(array('typeNotification' => 'notification.search'));
        $em = $this->getDoctrine()->getManager();

        $searchService = $this->get('app.search.offer');

        foreach ($notifications as $notification){
            $notifiDate = $notification->getDate();
            $finalArray = array();
            $offers = $searchService->searchOffer($notification->getElementId());
            foreach ($offers as $offer){
                $slot = $offer->getSlot();
                if($offer->getStartDate() > $notifiDate || ($offer->getCreationDate() > $notifiDate && isset($slot))){
                    $finalArray[] = $offer;
                }
            }

            if(!empty($offers)){
                $subject = 'Offer that might interest you';
                $mail = $notification->getMail();
                $mailer = $this->container->get('swiftmailer.mailer');

                $message = (new \Swift_Message('New offers could interest you'))
                    ->setFrom('jobnowlu@noreply.lu')
                    ->setTo($mail)
                    ->setBody(
                        $this->renderView(
                            'AppBundle:Emails:notification.html.twig',
                            array('offers' => $offers,
                                'subject' => $translated = $this->get('translator')->trans($subject),
                                'id' =>$notification->getId()
                            )
                        ),
                        'text/html'
                    )
                ;

                $mailer->send($message);
            }

            $notification->setDate($now);
            $em->merge($notification);
        }

        $em->flush();

        return new Response();
    }

}