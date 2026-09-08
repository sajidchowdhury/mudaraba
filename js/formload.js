// JavaScript Document
var Apost_office_one;

function makeRequest(url, parameters) {

      http_request = false;

      if (window.XMLHttpRequest) { // Mozilla, Safari,...

         http_request = new XMLHttpRequest();

         if (http_request.overrideMimeType) {

            http_request.overrideMimeType('text/xml');

         }

      } else if (window.ActiveXObject) { // IE

         try {

            http_request = new ActiveXObject("Msxml2.XMLHTTP");

         } catch (e) {

            try {

               http_request = new ActiveXObject("Microsoft.XMLHTTP");

            } catch (e) {}

         }

      }

      if (!http_request) {
         alert('Cannot create XMLHTTP instance');
         return false;
      }
	  
      http_request.onreadystatechange = alertContents;
      http_request.open('GET', url + parameters, true);
      http_request.send(null);

}

function alertContents() {

if (http_request.readyState == 4) {
    if (http_request.status == 200) {
		    // alert(Apost_office_one);
            // alert(http_request.responseText);
            result = http_request.responseText;
            
		document.getElementById(Apost_office_one).innerHTML = "";
        document.getElementById(Apost_office_one).innerHTML = result;
		$('.select2').select2();
		// FOR SALES CART
		if(result == 'Success!'){
			// alert("Add To Cart Is Success");
			// window.location.reload();
			$("#sales_cart_load").load("sales_cart_load.php");
		}
		
		// FOR SALES FINAL
		if(Apost_office_one == 'load_sales_msg2'){
			var q_inv = Number.isInteger(parseInt(result));
			if(q_inv){
				// alert("Sales Invoice Is Final Now.Please Collect Your Invoice From Printer.");	

				Swal.fire({
					icon: 'success',
					title: 'Invoice Create Success!!',
					showConfirmButton: false,
					timer: 1500
				  });

				window.open('sales_memo.php?inv='+result,'Memo Print','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=1,resizable=yes,copyhistory=no,width=500,height=500');
				// window.location.reload();
				form.reset();
				$("#sales_cart_load").load("sales_cart_load.php");
			}
		}
		// END
		
    }else{
        alert('There was a problem with the request.');
    }
    
	}
}



// SALES CART

function sales_data(stock_limit){
	Apost_office_one='load_sales_msg';
	var url="sales_product_action.php?data_sring=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	makeRequest(url, stock_limit);

}//close function brace

// SALES FINAL

function sales_final_data(data_sring2){
	Apost_office_one='load_sales_msg2';
	var url="sales_product_final.php?data_sring2=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	makeRequest(url, data_sring2);
}//close function brace


// CHECK DUE LIMIT

function check_due_limit(due_limit){
	Apost_office_one='load_due_limit';
	//alert(due_limit);
	var url="page_nav.php?cust_mobile=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	 makeRequest(url, due_limit);
	
	var a = document.getElementById('due').value;
	var b = document.getElementById('cart_total').value;//parseFloat(document.getElementById('cart_total').value);
	
	var c = a + b;
	
	document.forms["personal_details"]["net_total"].value = c;//document.getElementById('net_total').value = '11';

	
	}//close function brace
		

// UNIT PRICE

function find_unit_price(unit_price){
	Apost_office_one='load_unit_price';
	var url="page_nav.php?unit_price=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	 makeRequest(url, unit_price);

		}//close function brace	
		
// CHECK IN/EX BALANCE

function check_in_ex_limit(in_ex_limit){
	Apost_office_one='load_in_ex_limit';
	var url="page_nav.php?in_ex_limit=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	 makeRequest(url, in_ex_limit);

		}//close function brace		
		
// SUPPLIER DUE LIMIT

function check_due_limit2(due_limit){
	Apost_office_one='load_due_limit';
	//alert(due_limit);
	var url="page_nav.php?supplier_name=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';	
	 makeRequest(url, due_limit);
	 
		}//close function brace	
		
// ODC DEBIT AND CREDIT				
function getPageName_o_i_c_j(o_joma){
	Apost_office_one='load_o_joma_name';
	var url="other_joma_c_name.php?o_joma_id=";
	document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';
	makeRequest(url, o_joma, function() {
		$(".select2").select2();
	});
}
		

function getPageName_o_i_c_j_n(o_joma_name){
	Apost_office_one='load_o_joma_info';
	var url="other_joma_info.php?o_joma_name_id=";
document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';		
	 makeRequest(url, o_joma_name);
 		}//close function brace	

// function getPageName_o_i_c_k(o_cost){
// 	Apost_office_one='load_o_khoroj_name';
// 	var url="other_cost_c_name.php?o_cost_id=";
// document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';		
// 	 makeRequest(url, o_cost);
//  		}//close function brace	

// function getPageName_o_i_c_k_n(o_khoroj_name){
// 	Apost_office_one='load_o_khoroj_info';
// 	var url="other_khoroj_info.php?o_khoroj_name_id=";
// document.getElementById(Apost_office_one).innerHTML = '<img src=load.gif>';		
// 	 makeRequest(url, o_khoroj_name);
//  		}//close function brace			
		