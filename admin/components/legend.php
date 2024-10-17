<!-- Legend -->
<div class="legend">
    <div>
        <div class="legend-color normal"></div> Not Hazardous (&lt; 27°C)
    </div>
    <div>
        <div class="legend-color caution"></div> Caution (27°C - 32°C)
    </div>
    <div>
        <div class="legend-color extreme-caution"></div> Extreme Caution (33°C - 41°C)
    </div>
    <div>
        <div class="legend-color danger"></div> Danger (42°C - 51°C)
    </div>
    <div>
        <div class="legend-color extreme-danger"></div> Extreme Danger (&ge; 52°C)
    </div>
</div>


<style>
    .legend {
    display: flex;
    flex-wrap: wrap; /* Allow items to wrap to the next line */
    margin: 1rem 0; /* Add some margin for spacing */
}

.legend > div {
    display: flex;
    align-items: center; /* Center items vertically */
    margin: 0.5rem; /* Add margin for spacing between items */
    flex: 1 1 200px; /* Grow and shrink with a base size */
}

.legend-color {
    width: 20px; /* Fixed width for color boxes */
    height: 20px; /* Fixed height for color boxes */
    margin-right: 0.5rem; /* Space between color box and text */
}
</style>