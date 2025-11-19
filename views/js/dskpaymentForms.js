$(document).ready(function () {
    window.prestashop.component.initComponents([
        'ChoiceTable',
        'MultipleChoiceTable',
    ]);

    new window.prestashop.component.ChoiceTree('#form_category_choice_tree_type');
    new window.prestashop.component.ChoiceTree('#form_material_choice_tree_type');
    new window.prestashop.component.ChoiceTree(
        '#form_shop_choices_tree_type'
    ).enableAutoCheckChildren();
});