<style>
    #loading{
        position: fixed;
        top: 0; left:0; bottom: 0; right: 0;
        background-color: #0c0c0c8a;
        display: none;
        z-index: 1000;
    }
    #loading #img-div{
        max-width: 250px;
        max-height: 250px;
        position: fixed;
        top: 30%;
        left: 55%;
    }
</style>
<div id="loading">
	<div id="img-div">
		<img src="{{asset('img/images/ajax_loader_orange_128.gif')}}" alt="" class="img-responsive" />
	</div>
</div>