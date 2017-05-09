/* global d3 */
/* global $ */
/* global hex_sha256 */

var parseDate = d3.timeParse("%Y");
var currentJSONHash = null;
var currentData = null;


/**
 * Draws the graph using the d3 library and data from the database
 * @param json_data A JSON object of the data generated in php/query.php
 */
function draw(json_data, x_domain) {
	
	add_title();
	
	/* the categories */
	var keys = json_data.pop;

	/* the data */
	var data = process_JSON_to_D3(json_data, keys);

	var svg = d3.select("#display_svg").append("svg")
		.style("width", 960)
		.style("height", (($("#display_svg").width()) * 0.666))
		.attr("viewBox", "0 0 1080 " + (($("#display_svg").width()) * 0.666))
		.attr("preserveAspectRatio", "xMinYMin meet"),
		margin = {
			top: 20,
			right: 20,
			bottom: 30,
			left: 50
		};

	var disp_w = $("#display_svg").width();
	var disp_h = $("#display_svg").height();

	var width = 0;
	var height = 0;
	if (disp_h * 1.5 < disp_w) {
		width = disp_h * 1.5 - margin.left - margin.right;
		height = disp_h - margin.top - margin.bottom;
	}
	else {
		width = disp_w - margin.left - margin.right;
		height = disp_w * 0.66667 - margin.top - margin.bottom;
	}



	var x;

	if (x_domain === "Year") {
		x = d3.scaleTime().range([0, width]);
	}
	else {
		x = d3.scaleLinear().range([0, width]);
	}
	/* popularity for y-axis */
	var y = d3.scaleLinear().range([height, 0]),
		/* colours for each category */
		z = d3.scaleOrdinal(d3.schemeCategory10);

	var stack = d3.stack();

	/* creates the area on the graph for each category */
	var area = d3.area()
		.x(function(d, i) { // x-coordinates of the shape
			if (x_domain == "Year") {
				return x(parseDate(d.data.year));
			}
			else {
				return x(d.data.year);
			}
		})
		.y0(function(d, i) { // bottom y-coordinates of the shape
			if (isNaN(y(d[0]))) {
				return 0;
			}
			else {
				return y(d[0]);
			}
		})
		.y1(function(d) { //top y-coordinates of the shape
			if (isNaN(y(d[1]))) {
				return 0;
			}
			else {
				return y(d[1]);
			}
		});


	/* group in html to place resulting graph in */
	var g = svg.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

	/* the years */
	x.domain(d3.extent(data, function(d, i) {
		if (x_domain === "Year") {
			return parseDate(d.year);
		}
		else {
			return d.year;
		}
	}));

	/* colours */
	z.domain(keys);

	/* creates a stack consisting of the different categories */
	stack.keys(keys);

	/* creates layers consisting of each area graph for the different categories */
	var layer = g.selectAll(".layer")
		.data(stack(data))
		.enter().append("g")
		.attr("class", "layer");

	layer.append("path")
		.attr("class", "area")
		.style("fill", function(d, i) {
			return z(keys[i]);
		})
		.attr("d", area);

	g.append("g")
		.attr("class", "axis axis--x")
		.attr("transform", "translate(0," + height + ")")
		.call(d3.axisBottom(x));

	g.append("g")
		.attr("class", "axis axis--y")
		.call(d3.axisLeft(y).ticks(10, "%"));


	g.append("text")
		.attr("text-anchor", "middle") // this makes it easy to centre the text as the transform is applied to the anchor
		.attr("transform", "translate(" + (width / 2) + "," + (height + 50 + ")")) // centre below axis
		.text(x_domain);

	var legenndSVG = d3.select("#legend-div").append("svg");
	var legendG = legenndSVG.selectAll(".legend")
		.data(stack(data))
		.enter().append("g")
		.attr("transform", function(d, i) {
			return "translate(" + (0) + "," + (i * 15 + 20) + ")"; // place each legend on the right and bump each one down 15 pixels
		})
		.attr("class", "legend");

	legendG.append("rect") // make a matching color rect
		.attr("width", 10)
		.attr("height", 10)
		.attr("fill", function(d, i) {
			return z(keys[i]);
		});

	legendG.append("text") // add the text
		.text(function(d) {
			return " " + d.key;
		})
		.style("font-size", 12)
		.attr("y", 10)
		.attr("x", 11);
}

/**
 * Processes the received JSON object into a new object of which the data will
 * be used to draw the graph
 * @param json_data The json object that was generated in php/query.php
 * @param keys An array of 9 categories and 1 'other'
 * @return data The generated JSON object
 */
function process_JSON_to_D3(json_data, keys) {
	var data = [];

	if (hex_sha256(JSON.stringify(json_data)) == currentJSONHash) {
		data = currentData;
	}
	else {

		for (var year in json_data.data) {
			var entry = {};
			entry['year'] = parseInt(year);
			for (var index in json_data.data[year]) {
				entry[keys[index]] = json_data.data[year][index];
			}
			data.push(entry);
		}

		if (data.length == 1) {
			var entry = {};
			for (key in data[0]) {
				entry[key] = data[0][key];
			}
			entry['year']++;
			$.extend(data, {
				1: entry
			});
		}

		currentData = data;
		currentJSONHash = hex_sha256(JSON.stringify(json_data));
	}

	return data;
}
/**
 *  Adds the title to the graph
 */
function add_title() {
		$("#svg_header").empty();
	var title = "";
	switch ($("input[name=eco-filter-type]:checked").val()) {
		case "category":
			title = "Percentages of ECO Categories Over Time";
			break;
		case "code":
			title = "Percentages of ECO Codes Over Time";
			break;
		case "class":
			title = "Percentages of ECO Classes Over Time";
			break;
		case "year-eco-analysis":
			if ($("input[name='year']").val().length > 0) {
				title = "Percentages of ELO Values in " + $("input[name='year']").val();
			} else {
					title = "Percentages of ELO Values Over All Years";
			}
			
			break;
	}
	$("#svg_header").append("<h5>"+title+"</h5>");
}
