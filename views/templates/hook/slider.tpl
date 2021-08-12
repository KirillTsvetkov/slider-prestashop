<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>

<div class="splide">
	<div class="splide__track">
		<ul class="splide__list">
            
                {foreach from=$imgs item=img}
                <li class="splide__slide">
                     <img src='/modules/slider/img/{$img['url']}' style="width=700px;">
                      </li>
                {/foreach}
           
	    </ul>
	</div>
</div>

