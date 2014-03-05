<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\UserBundle\Controller\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Exception\InvalidPropertyPathException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as Controller;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Rest controller для работы с пользователями (users).
 *
 * @author Andrey Linko <AndreyLinko@gmail.com>
 */
class UserController extends Controller
{
    /**
     *
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Получение списка пользователей.",
     *  filters={
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"}
     *  }
     * )
     * 
     * @return Array Users
     */
    public function getUsersAction()
    {
        $limit = $this->container->get('request')->get('limit') ?: 25;
        $page = $this->container->get('request')->get('page') ?: 1;

        return $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'))
            ->getPage($page, $limit);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Создание нового пользователя.",
     *  formType="FOS\UserBundle\Form\Type\PostUserFormType"
     * )
     *
     * @return User.
     */
    public function postUsersAction()
    {
        $formFactory = $this->container->get('engage360d_rest.form.factory');
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager->createUser();
        $user->setEnabled(true);

        $form = $formFactory->createFormByRoute(
            $this->getRequest()->get('_route')
        );

        $form->setData($user);

        $form->bind($this->getRequest()->request->all());

        if (!$form->isValid()) {
            throw new HttpException(400, "User not valid.");
        }

        $userManager->updateUser($user);
        return $user;
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Получение детальной информации пользователя по id."
     * )
     * @param string $id User id property.
     *
     * @return User.
     */
    public function getUserAction($id)
    {
        return $this->container
            ->get('engage360d_rest.manager.user')
            ->findById($id);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Редактирование пользователя.",
     *  formType="FOS\UserBundle\Form\Type\PutUserFormType"
     * )
     * @param string $id User id property.
     *
     * @return User.
     */
    public function putUsersAction($id)
    {
        $formFactory = $this->container->get('engage360d_rest.form.factory');
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager
            ->findById($id, true);

        $form = $formFactory->createFormByRoute(
            $this->getRequest()->get('_route')
        );

        $form->setData($user);

        $form->bind($this->getRequest()->request->all());

        if (!$form->isValid()) {
            throw new HttpException(400, "User not valid.");
        }

        $userManager->updateUser($user);
        return $user;
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Удаление пользователя"
     * )
     * @param string $id User id property.
     *
     * @return Array.
     */
    public function deleteUserAction($id)
    {
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager->findById($id);
        $userManager->deleteUser($user);

        return array();
    }
}
