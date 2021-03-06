<?php
/**
 * Created by PhpStorm.
 * User: Altea IT
 * Date: 25/05/2018
 * Time: 08:53
 */

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=255)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Employer", cascade={"persist"}, inversedBy="user")
     * @ORM\JoinColumn(name="employer_id", referencedColumnName="id")
     */
    private $employer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="main", type="boolean")

     */
    protected $main = 0;


    /**
     * @ORM\Column(name="charge_id", type="string", length=255, nullable=true)
     */
    protected $chargeId;

    /**
     * @Assert\Regex(
     *  pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*).*$/",
     *  message="password.require"
     * )
     */
    protected $plainPassword;

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Tag
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Tag
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }



    /**
     * Set employer
     *
     * @param \AppBundle\Entity\Employer $employer
     *
     * @return User
     */
    public function setEmployer(\AppBundle\Entity\Employer $employer = null)
    {
        $this->employer = $employer;

        return $this;
    }

    /**
     * Get employer
     *
     * @return \AppBundle\Entity\Employer
     */
    public function getEmployer()
    {
        return $this->employer;
    }

    /**
     * Set chargeId
     *
     * @param string $chargeId
     *
     * @return User
     */
    public function setChargeId($chargeId)
    {
        $this->chargeId = $chargeId;

        return $this;
    }

    /**
     * Get chargeId
     *
     * @return string
     */
    public function getChargeId()
    {
        return $this->chargeId;
    }

    /**
     * @return bool
     */
    public function isMain()
    {
        return $this->main;
    }

    /**
     * @param bool $main
     * @return User
     */
    public function setMain($main)
    {
        $this->main = $main;
        return $this;
    }


}
