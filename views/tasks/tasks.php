<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="left-column">
    <h3 class="head-main head-task">Новые задания</h3>
    <?php foreach ($newTasks as $newTask): ?>
    <div class="task-card">
        <div class="header-task">
            <a  href="#" class="link link--block link--big"><?= $newTask->overview ?></a>
            <p class="price price--task"><?= $newTask->budget ?> ₽</p>
        </div>
        <p class="info-text"><span class="current-time">4 часа </span>назад</p>
        <p class="task-text"><?= $newTask->description ?></p>
        <div class="footer-task">
            <p class="info-text town-text"><?= $newTask->city->name ?? 'Удалённая работа' ?></p>
            <p class="info-text category-text"><?= $newTask->category->name ?></p>
            <a href="#" class="button button--black">Смотреть&nbsp;Задание</a>
        </div>
    </div>
    <?php endforeach ?>
    <div class="pagination-wrapper">
        <ul class="pagination-list">
            <li class="pagination-item mark">
                <a href="#" class="link link--page"></a>
            </li>
            <li class="pagination-item">
                <a href="#" class="link link--page">1</a>
            </li>
            <li class="pagination-item pagination-item--active">
                <a href="#" class="link link--page">2</a>
            </li>
            <li class="pagination-item">
                <a href="#" class="link link--page">3</a>
            </li>
            <li class="pagination-item mark">
                <a href="#" class="link link--page"></a>
            </li>
        </ul>
    </div>
</div>
<div class="right-column">
    <div class="right-card black">
        <?php
        $form = ActiveForm::begin([
            'id' => 'tasks-filter-form',
            'action' => '/tasks',
            'method' => 'get',
            'options' => ['class' => 'search-form'],
        ]); ?>

            <div class="fields-container">
                <fieldset class="fieldset">
                    <h4 class="head-card">Категории</h4>
                    <?= $form->field($categoryModel, 'id', [
                        'template' => '{input}',
                        'options' => ['tag' => false],
                    ])->checkBoxList($categories, [
                        'tag' => false,
                        'item' => function($index, $label, $name, $checked, $value) use ($selectedCategories) {
                            $checkedStatus = in_array($value, $selectedCategories) ? ' checked' : '';

                            return "<label><input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"$value\"$checkedStatus> "
                            . Html::encode($label)
                            . "</label>";
                        },
                        'unselect' => null,

                    ]) ?>
                </fieldset>

                <fieldset class="fieldset">
                    <h4 class="head-card">Дополнительно</h4>
                    <?= $form->field($taskModel, 'city_id', [
                        'template' => '{input}{label}',
                        'options' => ['tag' => false],
                    ])->checkbox([
                        'uncheck' => null,
                        'label' => 'Удалённая работа',
                        'name' => 'showRemoteOnly',
                        'value' => 1,
                        'checked' => $shouldShowRemoteOnly ? '' : null,
                        'class' => 'checkbox',
                    ]) ?>
                    <?= $form->field($taskModel, 'id', [
                        'template' => '{input}{label}',
                        'options' => ['tag' => false],
                    ])->checkbox([
                        'uncheck' => null,
                        'label' => 'Без откликов',
                        'name' => 'showWithoutResponses',
                        'value' => 1,
                        'checked' => $shouldShowWithoutResponses ? '' : null,
                        'class' => 'checkbox',
                    ]) ?>
                </fieldset>

                <fieldset class="fieldset">
                    <h4 class="head-card">Период</h4>
                    <?= $form->field($taskModel, 'date_created', ['template' => '{input}'])->dropDownList(
                        [
                            '01:00:00' => '1 час',
                            '12:00:00' => '12 часов',
                            '24:00:00' => '24 часа',
                        ],
                        [
                            'prompt' => 'Выберите период',
                            'options' => [
                                $selectedPeriod => ['selected' => ''],
                            ],
                        ]
                    ) ?>
                </fieldset>
            </div>

            <?= HTML::submitButton('Искать', ['class' => 'button button--blue']); ?>
        <?php ActiveForm::end() ?>
    </div>
</div>
