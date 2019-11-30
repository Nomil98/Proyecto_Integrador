<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="chart.js/Chart.js"></script>
    <title>Datos registrados</title>
</head>
<body>
   
        <canvas id="myChart" width="800" height="400"></canvas>
        <script>
        var grafica = document.getElementById('myChart');
        var myLineChart = new Chart(grafica, {
    type: 'line',
    data:{
            labels:["12:00am", "1:00am","2:00am","3:00am","4:00am","5:00am","6:00am","7:00am","8:00am","9:00am","10:00am","11:00am","12:00pm","1:00pm","2:00pm","3:00pm","4:00pm","5:00pm   " ],
            datasets:[
                {
                    label: "Lunes",
                    borderColor: "blue", 
                    data:[23,25,25,25,25,26,26,26,27,27,28,28,28,27,27,25,25,25],
                    fill: false
                },
                
                {
                    label: "Martes",
                    borderColor: "red", 
                    data:[22,24,24,24,24,26,26,26,25,25,25,24,24,24,25,25,25,26],
                    fill: false

                },

                {
                    label: "Miércoles",
                    borderColor: "gray", 
                    data:[22,22,22,23,23,24,24,24,24,25,25,25,25,25,26,26,26,26],
                    fill: false

                },

                {
                    label: "Jueves",
                    borderColor: "green", 
                    data:[25,25,25,25,25,26,26,26,27,27,28,28,29,29,31,31,33,33],
                    fill: false

                },

                {
                    label: "Viernes",
                    borderColor: "pink", 
                    data:[23,25,25,25,25,26,26,26,27,27,28,28,30,30,31,31,34,34],
                    fill: false

                },

                {
                    label: "Sábado",
                    borderColor: "black", 
                    data:[28,28,28,28,29,29,29,30,31,32,33,33,34,34,35,35,35,35],
                    fill: false

                },
            ]
    },
    options:{
        title:{
            display: true,
            text: 'Temperatura Ambiental capturada'
        }
    }
    
});
        </script>
    
</body>
</html>