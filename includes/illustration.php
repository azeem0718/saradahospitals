<?php
/**
 * Line-art illustration of the nursing home, drawn for this site.
 *
 * Every stroked shape carries pathLength="1", which normalises the dash maths:
 * the draw-on animation then uses a dasharray and dashoffset of 1 whatever the
 * real path length is, so no line outruns another. The d1–d5 groups stagger
 * the reveal: ground, then massing, then windows, then the details.
 */

declare(strict_types=1);

function hospital_illustration(): string
{
    return <<<'SVG'
    <svg class="draw-art" viewBox="0 0 620 420" role="img"
         aria-label="Line illustration of Sarada Nursing Home">
      <g fill="none" stroke="currentColor" stroke-width="2.4"
         stroke-linecap="round" stroke-linejoin="round">

        <!-- ground ------------------------------------------------------ -->
        <g class="d1">
          <path pathLength="1" d="M26 372h568"/>
        </g>

        <!-- massing ----------------------------------------------------- -->
        <g class="d1">
          <!-- tower -->
          <path pathLength="1" d="M310 372V112h160v260"/>
          <path pathLength="1" d="M302 112h176"/>
          <!-- front block -->
          <path pathLength="1" d="M110 372V200h200"/>
          <path pathLength="1" d="M102 200h216"/>
        </g>

        <!-- roof sign + floor lines ------------------------------------- -->
        <g class="d2">
          <path pathLength="1" d="M352 112V80h56v32"/>
          <path pathLength="1" d="M344 80h72"/>
          <path pathLength="1" d="M310 164h160M310 216h160M310 268h160M310 320h160"/>
          <path pathLength="1" d="M110 252h200M110 304h200"/>
        </g>

        <!-- windows ----------------------------------------------------- -->
        <g class="d3">
          <path pathLength="1" d="M330 128h30v24h-30zM375 128h30v24h-30zM420 128h30v24h-30z"/>
          <path pathLength="1" d="M330 180h30v24h-30zM375 180h30v24h-30zM420 180h30v24h-30z"/>
          <path pathLength="1" d="M330 232h30v24h-30zM375 232h30v24h-30zM420 232h30v24h-30z"/>
          <path pathLength="1" d="M330 284h30v24h-30zM375 284h30v24h-30zM420 284h30v24h-30z"/>
          <path pathLength="1" d="M130 216h34v24h-34zM184 216h34v24h-34zM238 216h34v24h-34z"/>
          <path pathLength="1" d="M130 268h34v24h-34zM238 268h34v24h-34z"/>
        </g>

        <!-- entrance ---------------------------------------------------- -->
        <g class="d4">
          <path pathLength="1" d="M178 372v-56h64v56"/>
          <path pathLength="1" d="M210 316v56"/>
          <path pathLength="1" d="M166 316h88"/>
          <path pathLength="1" d="M170 302h80l-8 14h-64z"/>
          <path pathLength="1" d="M156 372h108"/>
        </g>

        <!-- cross sign on the roof box ---------------------------------- -->
        <g class="d4">
          <path pathLength="1" d="M380 88v16M372 96h16" stroke-width="3.4"/>
        </g>

        <!-- ambulance, clear of the buildings on the right --------------- -->
        <g class="d5">
          <path pathLength="1" d="M496 372v-46h62v46"/>
          <path pathLength="1" d="M558 344h20l14 18v10h-34"/>
          <path pathLength="1" d="M562 348h14l9 12h-23z"/>
          <circle pathLength="1" cx="518" cy="372" r="10"/>
          <circle pathLength="1" cx="570" cy="372" r="10"/>
          <path pathLength="1" d="M520 338v14M513 345h14"/>
        </g>

        <!-- planting ----------------------------------------------------- -->
        <g class="d5">
          <path pathLength="1" d="M62 372v-40"/>
          <circle pathLength="1" cx="62" cy="316" r="22"/>
          <path pathLength="1" d="M604 372v-26"/>
          <circle pathLength="1" cx="604" cy="334" r="15"/>
        </g>
      </g>
    </svg>
    SVG;
}
