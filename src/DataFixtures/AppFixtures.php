<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $course1 = new Course();

        $course1->setName('ИТ-инженер');
        $course1->setCode('course-1');
        $course1->setDescription('Станьте ИТ-инженером и получите одну из самых востребованных профессий.' .
            'Вы изучите основы программирования и основные концепции 
            компьютерных наук, цифровые технологии, программное обеспечение, операционные системы,' .
            'базы данных, системы аналитики, языки программирования и многое другое. ' .
            'Вы так же познакомитесь с тестированием и системным анализом. информационных технологий. ' .
            'На программе сможете сделать осознанный выбор специализации и 
            технологий, прокачаться в выбранном направлении.');

        $manager->persist($course1);
        $manager->flush();

        $lesson1_1 = new Lesson();
        $lesson1_1->setCourse($course1);
        $lesson1_1->setName('Введение в программирование');
        $lesson1_1->setContent('Расскажем, как спланировать обучение,'.
        'чтобы сохранить интерес, получить максимум пользы и всё успеть.');
        $lesson1_1->setSerialNumber('1');

        $lesson1_2 = new Lesson();
        $lesson1_2->setCourse($course1);
        $lesson1_2->setName('Знакомство с языками программирования');
        $lesson1_2->setContent('Познакомитесь с языками программирования:'.
        'функциями и массивами, рекурсиями и двумерными массивами.' .
            'Узнаете, как нужно писать код.');
        $lesson1_2->setSerialNumber('2');

        $lesson1_3 = new Lesson();
        $lesson1_3->setCourse($course1);
        $lesson1_3->setName('Знакомство с базами данных');
        $lesson1_3->setContent('Познакомитесь с понятием «базы данных», ' .
        'разберетесь с их видами и основными подходами к работе с данными.' .
            'Узнаете методы проектирования баз данных, а также способы модификации их структуры.');
        $lesson1_3->setSerialNumber('3');

        $lesson1_4 = new Lesson();
        $lesson1_4->setCourse($course1);
        $lesson1_4->setName('Итоги блока. Выбор специализации');
        $lesson1_4->setContent('Изучите колесо компетенций и матрицу Декарта. ' .
        'Познакомитесь со специализациями и выберете дальнейшее направление развития.');
        $lesson1_4->setSerialNumber('4');

        $manager->persist($lesson1_1);
        $manager->persist($lesson1_2);
        $manager->persist($lesson1_3);
        $manager->persist($lesson1_4);
        $manager->flush();


        $course2 = new Course();

        $course2->setName('Профессия Графический дизайнер');
        $course2->setCode('course-2');
        $course2->setDescription('Вы с нуля получите востребованную профессию на стыке' .
            'творчества и IT. Научитесь работать в популярных графических' .
            'редакторах — от Illustrator до Figma. Добавите в портфолио плакаты,' .
            'логотипы, дизайн упаковки и другие сильные проекты. ' .
            'Сможете начать зарабатывать уже с 4-го месяца курса.');

        $manager->persist($course2);
        $manager->flush();

        $lesson2_1 = new Lesson();
        $lesson2_1->setCourse($course2);
        $lesson2_1->setName('Шрифт в дизайне');
        $lesson2_1->setContent('Что такое шрифт и как люди его видят'.
                                        'Какие бывают шрифты'.
                                        'Пропорции, насыщенность, контраст и другие параметры шрифта'.
                                        'Как выбрать шрифт для проекта'.
                                        'Знаковый состав шрифта'.
                                        'Оцениваем качество шрифта'.
                                        'Платные и бесплатные шрифты'.
                                        'Выбираем шрифт с характером'.
                                        'Современные шрифтовые технологии'.
                                        'Микротипографика');
        $lesson2_1->setSerialNumber('1');

        $lesson2_2 = new Lesson();
        $lesson2_2->setCourse($course2);
        $lesson2_2->setName('Дизайнер упаковки');
        $lesson2_2->setContent('Как дизайнер упаковки влияет на продажи и любовь к бренду'.
                                        'Психология выбора'.
                                        'Позиционирование бренда'.
                                        'Создаём концепцию бренда'.
                                        'Визуализация: от метафоры к наброскам'.
                                        'Цвет в брендинге: подбираем палитру для проекта'.
                                        'Логотип и типографика'.
                                        'Как быть похожим и непохожим на конкурентов'.
                                        'Стекло, пластик, бумага и другие материалы упаковки'.
                                        'Какой дизайн можно считать успешным и как заказчик оценивает работу'.
                                        'Плюс 5 модулей.');
        $lesson2_2->setSerialNumber('2');

        $lesson2_3 = new Lesson();
        $lesson2_3->setCourse($course2);
        $lesson2_3->setName('Photoshop с нуля');
        $lesson2_3->setContent('Знакомимся с Photoshop'.
                                        'Основные инструменты программы'.
                                        'Работа с файлами и изображениями'.
                                        'Принципы работы со слоями и масками'.
                                        'Бонус-модуль. Выделение и маски'.
                                        'Артборды и типы слоёв'.
                                        'Добавляем эффекты'.
                                        'Продвинутые приёмы обтравки и ретуши'.
                                        'Принципы построения растровых изображений'.
                                        'Бонус-модуль. Как освоить Pen Tool');
        $lesson2_3->setSerialNumber('3');

        $lesson2_4 = new Lesson();
        $lesson2_4->setCourse($course2);
        $lesson2_4->setName('Figma 2.0');
        $lesson2_4->setContent('Знакомимся с Figma'.
                                        'Фигуры и изображения: создаём сет иконок'.
                                        'Модульные сетки и фреймы'.
                                        'Что такое компоненты и как с ними работать'.
                                       'Волшебная функция Auto Layout'.
                                        'Как подготовить файл к передаче в разработку'.
                                        'Прототипирование'.
                                        'Добавляем анимацию с помощью Figma Animate'.
                                        'Бонус-модуль. Как ускорить работу с Figma Plugins');
        $lesson2_4->setSerialNumber('4');

        $manager->persist($lesson2_1);
        $manager->persist($lesson2_2);
        $manager->persist($lesson2_3);
        $manager->persist($lesson2_4);
        $manager->flush();


        $course3 = new Course();

        $course3->setName('Профессия Бухгалтер');
        $course3->setCode('course-3');
        $course3->setDescription('Вы с нуля научитесь вести бухучёт по российским стандартам' .
            'и работать в 1С, готовить налоговую отчётность и рассчитывать' .
            'зарплату. Сможете начать карьеру бухгалтера в России или получить повышение');

        $manager->persist($course3);
        $manager->flush();

        $lesson3_1 = new Lesson();
        $lesson3_1->setCourse($course3);
        $lesson3_1->setName('Основы бухгалтерского учёта');
        $lesson3_1->setContent('Введение: термины, понятия, нормативная база.'.
                                        'Объекты бухгалтерского учёта.'.
                                        'Принцип двойной записи. Проводки.'.
                                        'Формирование и обработка первичной документации.'.
                                        'Ведение участка «Расчётный счёт».'.
                                        'Ведение участков «Касса» и «Авансовые отчёты».'.
                                        'Ведение участка «Зарплата и кадры».'.
                                        'Ведение участка «Покупатели».'.
                                        'Ведение участка «Поставщики».'.
                                        'Ведение участка «Кредиты и займы».'.
                                        'Ведение участка «Склад».'.
                                        'Ведение участка «Производство».');
        $lesson3_1->setSerialNumber('1');

        $lesson3_2 = new Lesson();
        $lesson3_2->setCourse($course3);
        $lesson3_2->setName('Налоги и налогообложение');
        $lesson3_2->setContent('Налоговый кодекс.'.
                                        'Учётная политика для целей налогового учёта.'.
                                        'Виды налогов и взносов.'.
                                        'Системы налогообложения.'.
                                        'Налоговый учёт.'.
                                        'Налоговая отчётность. Отчётность в фонды. Состав и сроки сдачи.'.
                                        'Налоговое администрирование.'.
                                        'Налоговые проверки.'.
                                        'Практикум: выбрать систему налогообложения' .
                                        'и систему отчётности для организации.');
        $lesson3_2->setSerialNumber('2');

        $lesson3_3 = new Lesson();
        $lesson3_3->setCourse($course3);
        $lesson3_3->setName('1С:Бухгалтерия 8, редакция 3.0');
        $lesson3_3->setContent('Начальная настройка программы.'.
                                        'Работа со справочниками.'.
                                        'Ввод хозяйственных операций. Учёт банковских и кассовых операций.'.
                                        'Учёт валютных операций.'.
                                        'Учёт расчётов с подотчётными лицами.'.
                                        'Учёт основных средств и НМА.'.
                                        'Учёт товарных операций.'.
                                        'Внешнеторговые операции. Импорт и экспорт.'.
                                        'Расчёты по оплате труда.'.
                                        'Материальный учёт. Складские операции.'.
                                        'Производство продукции.');
        $lesson3_3->setSerialNumber('3');

        $manager->persist($lesson3_1);
        $manager->persist($lesson3_2);
        $manager->persist($lesson3_3);
        $manager->flush();
    }
}
