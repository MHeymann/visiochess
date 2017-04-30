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
function draw(json_data) {

  /* area in html for graph */

  var svg = d3.select("#display_svg").append("svg")
    .style("width", 960)
    .style("height", 500)
    .attr("viewBox", "0 0 960 500")
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


  /* the years for x-axis*/
  var x = d3.scaleTime().range([0, width]),
    /* popularity for y-axis */
    y = d3.scaleLinear().range([height, 0]),
    /* colours for each category */
    z = d3.scaleOrdinal(d3.schemeCategory10);

  var stack = d3.stack();

  /* creates the area on the graph for each category */
  var area = d3.area()
    .x(function(d, i) { // x-coordinates of the shape
      return x(parseDate(d.data.year));
    })
    .y0(function(d, i) { // bottom y-coordinates of the shape
      return y(d[0]);
    })
    .y1(function(d) { //top y-coordinates of the shape
      return y(d[1]);
    });


/* group in html to place resulting graph in */
  var g = svg.append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  /* the categories */
  var keys = json_data.pop;

  /* the data */
  var data = process_JSON_to_D3(json_data, keys);

  /* the years */
  x.domain(d3.extent(data, function(d, i) {
    return parseDate(d.year);
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

  layer.filter(function(d) {
      return d[d.length - 1][1] - d[d.length - 1][0] > 0.01;
    })
    .append("text")
    .attr("x", width - 6)
    .attr("y", function(d) {
      return y((d[d.length - 1][0] + d[d.length - 1][1]) / 2);
    })
    .attr("dy", ".35em")
    .style("font", "10px sans-serif")
    .style("text-anchor", "end")
    .text(function(d) {
      return d.key;
    });

  g.append("g")
    .attr("class", "axis axis--x")
    .attr("transform", "translate(0," + height + ")")
    .call(d3.axisBottom(x));

  g.append("g")
    .attr("class", "axis axis--y")
    .call(d3.axisLeft(y).ticks(10, "%"));
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
    /* 'data' from json (would be better if array received from php)*/

    Object.keys(json_data.data).forEach(function(key, i) {
      var entry = {};
      entry['year'] = parseInt(key);
      json_data.data[key].forEach(function(v, i) {
        entry[keys[i]] = v;
      });
      data.push(entry);
    });
    currentData = data;
    currentJSONHash = hex_sha256(JSON.stringify(json_data));
  }

  return data;
}
