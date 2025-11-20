<?php

declare(strict_types=1);

namespace PrestaShop\Module\DskPayment\Form;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\FormBuilderInterface;

class DskPaymentConfigurationType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'dskapi_status',
                SwitchType::class,
                [
                    'label' => 'DSK Credit API покупки на Кредит',
                    'help' => 'Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit API.',
                ]
            )
            ->add('dskapi_cid', TextType::class, [
                'label' => 'Уникален идентификатор на магазина',
                'help' => 'Уникален идентификатор на магазина в системата на DSK Credit API.',
                'required' => true,
            ])
            ->add(
                'dskapi_reklama',
                SwitchType::class,
                [
                    'label' => 'Визуализиране на реклама',
                    'help' => 'Можете да включвате или изключвате показването на реклама в началната страница на магазина.',
                ]
            )
            ->add('dskapi_gap', TextType::class, [
                'label' => 'Свободно място над бутона',
                'help' => 'Свободно място над бутона в px.',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'type' => 'number',
                    'placeholder' => '0',
                ],
            ]);
    }
}
