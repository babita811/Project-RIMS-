<?php

//Indexed array
$fruits = array("litchi","mango","apple","banana","cherry","date");
$new_fruits = ["avacado","dragon fruit"];
$merges_array = array_merge($fruits,$new_fruits);

foreach($merges_array as $fruit){
    echo $fruit . "\n";
}
echo "<br>";
// $sorted = sort($fruits);
// var_dump($sorted);

var_dump(in_array("apple",$new_fruits));
var_dump(in_array("apple",$fruits));
var_dump(in_array("apple",$merges_array));



// echo in_array("apple",$new_fruits);
// echo in_array("apple",$fruits);
// echo in_array("apple",$merges_array);
// print_r(sort($fruits));

// echo $fruits[2];
// $fruits[2] = "Berry";
// array_push($fruits,"strawberry");
// array_unshift($fruits, "Watermelon");

// foreach($fruits as $abc){
//     echo $abc . "\n";
//  }

//  echo "<br>";
//  array_pop($fruits);
//  array_shift($fruits);
//  unset($fruits[2]);
//  foreach($fruits as $abc){
//     echo $abc . "\n";
//  }
//  echo "<br>";

//  //Associative Array
//  $students = ["name" => "Babita","age"=> 23,"course" => "BCA"];
//  echo $students["name"];
//   echo "<br>";

 
//  foreach($students as $key => $std){
//     echo  ucfirst($key). ":" .$std . "\n";
//   }

//    echo "<br>";

// //Multidimensional array   
// $animals = [s
//     ["name" => "Lion","color"=>"Brown"],
//     ["name" => "Parrot","color"=>"Green"],
//     ["name" => "Frog","color"=>"Grey"],   
// ];

// echo $animals[0]["name"];
// $animals[0]["name"] ="Tiger";
// print_r($animals[0]); 

// $animals[0] = ["name" => "Zebra" ,"color" => "Blue"];
// print_r($animals[0]);

// foreach($animals as $animal){
//     // echo $animal;
//     foreach($animal as $key => $value){
//         echo $key . ":" . $value . "\n";
//     }
//         echo "<br>";


    
// }