<?php

declare(strict_types=1);

namespace PrestaShop\Module\DskPayment\Controller;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Context;
use Db;
use DbQuery;

class DskPaymentConfigurationController extends FrameworkBundleAdminController
{

    public function index(Request $request): Response
    {
        $textFormDataHandler = $this->get('prestashop.module.dskpayment.dskpayment_configuration_form_handler');

        $textForm = $textFormDataHandler->getForm();
        $textForm->handleRequest($request);

        if ($textForm->isSubmitted() && $textForm->isValid()) {
            $errors = $textFormDataHandler->save($textForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', 'Успешна актуализация');

                return $this->redirectToRoute('dskpayment_configuration_form');
            }

            $this->flashErrors($errors);
        }

        $context = Context::getContext();

        return $this->render('@Modules/dskpayment/views/templates/admin/form.html.twig', [
            'dskPaymentConfigurationForm' => $textForm->createView(),
        ]);
    }
}
