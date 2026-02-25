<?php
// function sum($x, $y=7){
//     echo $x+$y . "<br>";
// }
// // sum();
// sum(0, 2);
// sum(10);

// function sum1($x, $y=7){
//     echo $x+$y . "<br>";

// }
// sum();
// sum(0, 2);
// sum(10);

// $num = 60;
// function local_var() {
//     global $num;
//     $num = 50;
//     echo "local num = $num <br>";    
// }
// local_var();
// echo "Variable num outside local_var() function is $num \n";

// $message = "Hello, World";
// function printMessage(){
//     echo $GLOBALS['message'];
// }
// printMessage();

function incrementCounter() {
    static $counter = 0;
    $counter ++;
    echo "Counter: $counter\n";
}
incrementCounter();
incrementCounter();
incrementCounter();
?>

