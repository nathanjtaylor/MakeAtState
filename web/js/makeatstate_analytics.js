 $(document).ready(function(){
	var type = 1;
	var myChart;
 	current = new Date();
	var current_year = current.getFullYear();
	preProcessAnalytics(current_year, type);
	
	function preProcessAnalytics(current_year){
		var selected_year = current_year;
	 	var url = "\?t=ajax";	
		var options = {options : {selected_year:selected_year ,dtype:type,  path: 'stats', func:'prepareAnalytics'}};
		getAnalytics("GET", options, url);

	}
	
	// ajax call to get prime analytics
	function getAnalytics(type, options, url){
		
	
		$.ajax({
		  type: type,
		  url: url,
		  data: options,
		  dataType: 'json',
		  success: function ( data, status){
		  	console.log(data);
			prepareAnalytics(data)
		  }
		});

	}
 	

	function prepareAnalytics(data){
		var ctx = document.getElementById("myChart");
		ctx.height = 500;
		var selected_year = data['selected_year'];
		var years = $.map(data['years'], function(el) { return el });
		makeYears(selected_year, years);
		var submitted = $.map(data['submitted'], function(el) { return el });
		var completed  = $.map(data['completed'], function(el) { return el });
		var user_cancelled= $.map(data['user_cancelled'], function(el) { return el });
		var staff_cancelled = $.map(data['staff_cancelled'], function(el) { return el });
		$.each(data , function(key, value){
				if(key == "submitted"){
					submitted.push(value);
				}
		});
		if(type ==1){
			myChart = new Chart(ctx, {
				type: 'line',
				data: {
				    labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
				    fill: false,
					datasets: [{
					      label: 'Jobs submitted',
							data: submitted,
							borderColor :'rgba(40, 167, 69,1)',
							backgroundColor :'rgba(40, 167, 69,0)'
						},
						{
					      label: 'Jobs completed',
							data: completed,
							borderColor :'rgba(81, 97, 132, 1)',
							backgroundColor :'rgba(81, 97, 132, 0)'
						},
						{
					      label: 'Jobs cancelled by user',
							data: user_cancelled,
							borderColor :'rgba(255, 226, 48, 1)',
							backgroundColor :'rgba(255, 226, 48, 0)'
						}, 
						{
						label: 'Jobs cancelled by makerspace',
							data: staff_cancelled,
							borderColor :'rgba(255, 0, 89, 1)',
							backgroundColor: "rgba(255, 0, 89, 0)"
						}
					]
				},
				 options: {
					maintainAspectRatio: false,
					scales: {
						yAxes: [{
							tics:{
								 min: 0,
								 max:100,
								 stepSize:10
							}
						}]
					}
				 }
			});
		}else{

			myChart = new Chart(ctx, {
				type: 'line',
				data: {
				    labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
				    fill: false,
					datasets: [{
					      label: 'Dollar amount received',
							data: completed,
							borderColor :'rgba(81, 97, 132, 1)',
							backgroundColor :'rgba(81, 97, 132, 0)'
						},
						{
					      label: 'Dollar amount cancelled by user ',
							data: user_cancelled,
							borderColor :'rgba(255, 226, 48, 1)',
							backgroundColor :'rgba(255, 226, 48, 0)'
						}, 
						{
						label: 'Dollar amount cancelled by Makerspace',
							data: staff_cancelled,
							borderColor :'rgba(255, 0, 89, 1)',
							backgroundColor: "rgba(255, 0, 89, 0)"
						}
					]
				},
				 options: {
					maintainAspectRatio: false,
					scales: {
						yAxes: [{
							tics:{
								 min: 0,
								 max:100,
								 stepSize:10
							}
						}]
					}
				 }
			});



		}

	}



	$('.prime_ajax_tab').on('click', function(e){
		e.preventDefault();
		var id = this.id;
		type = id.split("_")[1];
		$('.prime_ajax_tab').removeClass('prime_background_twinkleblue');
		$('#'+id).addClass('prime_background_twinkleblue');
		myChart.destroy();
		preProcessAnalytics(current_year, type);


	});
	$('.years').on('click', '.year_value', function(){
		current_year = this.id;
		myChart.destroy();
		preProcessAnalytics(current_year, type);
		
	});

	function makeYears(selected_year, years){
		var html = "";
		$.each(years, function(k,val){
			if(selected_year == val ){
				html += '<div class="col-1 prime_background_twinkleblue prime_border_twinkleblue prime_textalign_center prime_borderradius20px year_value  " id = '+ val+' > '+ val+' </div>';

			}
			else{
				html += '<div class="col-1 prime_border_twinkleblue prime_textalign_center prime_borderradius20px year_value  " id = '+ val+' > '+ val+' </div>';
			}
		});
		$('.years').html(html);
	}

 });
