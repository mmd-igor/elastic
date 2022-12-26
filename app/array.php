
<?php

// $inArr -  исходный массив товаров
// $inCnt -  количество остатков, выше которого нужен запрос

$inArr = ['A' => null, 'B' => 'sdfsdf', 'C' => null];

rsort($inArr);
print_r($inArr);
exit;

$inCnt = 44;

//-------------------------------------------------

const INCNT = 100;
for ($i = 0; $i < INCNT; ++$i) {
    $inArr[] = ['name' => 'Товар №' . $i, 
        'article' => 'PH-01-03', 
        'quantity' => rand(0, 4) ? random_int(1, 99) : 0,
        'price' => random_int(10000, 99999) / 100
    ];
}

//-------------------------------------------------

$cnt = count($inArr); // количество товара на входе
// всегда делай проверки входных данных!
if ($cnt == 0) {
    print("Нет товаров в массиве");
    exit;
}

$maxPrice = 0;        // сюда положим самую дорогую позицию в массиве
$maxPosition = -1;    // позиция максимальной цены в массиве

// все 3 задания делаем в один проход массива
for ($i=0; $i<$cnt; $i++) {
    // поиск нулевых остатков
    if ($inArr[$i]['quantity'] == 0) printf("Нет на складе: %s (артикул: %s)\n", $inArr[$i]['name'], $inArr[$i]['article']);
    //  поиск остатков выше заданного числа
    if ($inArr[$i]['quantity'] > $inCnt) printf("Запас товара %s (артикул: %s) на складе: %d\n", $inArr[$i]['name'], $inArr[$i]['article'], $inArr[$i]['quantity']);
    // поиск самой дорогой позиции
    if ($inArr[$i]['price'] > $maxPrice) {
        $maxPrice = $inArr[$i]['price'];
        $maxPosition = $i;
    }     
}

printf("Самый дорогой товар %s (артикул: %s) - цена: %.2f \n", $inArr[$maxPosition]['name'], $inArr[$maxPosition]['article'], $inArr[$maxPosition]['price']);
// примечание - если в массиве несколько товаров с одинаковой максимальной ценой, то в результат попадает первый, считая от начала массива