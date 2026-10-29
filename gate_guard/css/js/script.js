// ===============================
// Gate Guard RFID Monitoring System
// script.js
// ===============================

// Auto Refresh Attendance Page Every 5 Seconds
if(window.location.pathname.includes("attendance.php")){

    setInterval(function(){

        location.reload();

    },5000);

}

// Delete Confirmation
function confirmDelete(){

    return confirm("Are you sure you want to delete this student?");

}

// Show Current Time
function updateClock(){

    let now = new Date();

    let time = now.toLocaleTimeString();

    let clock = document.getElementById("clock");

    if(clock){

        clock.innerHTML = time;

    }

}

setInterval(updateClock,1000);

// Search Table
function searchStudent(){

    let input = document.getElementById("search");

    if(!input) return;

    let filter = input.value.toUpperCase();

    let table = document.getElementById("studentTable");

    if(!table) return;

    let tr = table.getElementsByTagName("tr");

    for(let i=1;i<tr.length;i++){

        let td = tr[i].getElementsByTagName("td")[2];

        if(td){

            let txtValue = td.textContent || td.innerText;

            if(txtValue.toUpperCase().indexOf(filter) > -1){

                tr[i].style.display="";

            }else{

                tr[i].style.display="none";

            }

        }

    }

}