
  function jSliderInputDeal($obj){
    $objArr = $obj.parent().children('input[type="text"]');
        if($objArr.length == 2){
            var searchLeft = parseFloat($objArr.eq(0).val().replace(/[^\d.]/g,''));
            var searchRight = parseFloat($objArr.eq(1).val().replace(/[^\d.]/g,''));
            if($obj.attr('key') == 'priStoneWeight'){
                $objTmp = $('.priStoneWeight input[type="text"]');
                $("#Slider1").val($objTmp.eq(0).val()+';'+$objTmp.eq(1).val());
                //价格拖动效果
                jQuery("#Slider1").slider(
                    "value", searchLeft, searchRight
                );
            }
            if($obj.attr('key') == 'salePrice'){
                $objTmp = $('.salePrice input[type="text"]');
                $("#Slider2").val($objTmp.eq(0).val()+';'+$objTmp.eq(1).val());
                //重量拖动效果
                jQuery("#Slider2").slider(
                    "value", searchLeft, searchRight
                );
            }
            jSliderControl();
        }
    }

    //手输钻重或者价格后执行此方法
    function jSliderInput(){
        if( $('#diy_border input[type="text"]').length > 0 ){
            $('#diy_border input[type="text"]').blur(function() {
                jSliderInputDeal($(this));
            });
            $('#diy_border input[type="text"]').keydown(function(e){
                if(e.keyCode==13){
                    $(this).blur();
                    jSliderInputDeal($(this));
                }
            });
        }
    }

    //拖动后执行此方法
    var S1_val = '';
    var S2_val = '';
    var salePrice,salePriceArr = '';
    var priStoneWeight,priStoneWeightArr = '';
    function jSliderControl(){
        if('' == S1_val){
            S1_val = $("#Slider1").val();
            s1valArr= $("#Slider1").val().split(";");
            priStoneWeight = s1valArr[0].replace(/[^\d.]/g,'')+"-"+s1valArr[1].replace(/[^\d.]/g,'');
        }else{
            if(S1_val != $("#Slider1").val()){
                S1_val = $("#Slider1").val();
                s1valArr= $("#Slider1").val().split(";");
                priStoneWeight = s1valArr[0].replace(/[^\d.]/g,'')+"-"+s1valArr[1].replace(/[^\d.]/g,'');
            }
        }
        salePriceArr = priStoneWeight.split("-");
        // console.log(salePriceArr[0]);
        // console.log(salePriceArr[1]);
        if('' == S2_val){
            S2_val = $("#Slider2").val();
            s2valArr = $("#Slider2").val().split(";");
            salePrice = s2valArr[0].replace(/[^\d.]/g,'')+"-"+s2valArr[1].replace(/[^\d.]/g,'');
        }else{
            if(S2_val != $("#Slider2").val()){
                S2_val = $("#Slider2").val();
                s2valArr = $("#Slider2").val().split(";");
                salePrice = s2valArr[0].replace(/[^\d.]/g,'')+"-"+s2valArr[1].replace(/[^\d.]/g,'');
            }
        }
        priStoneWeightArr = salePrice.split("-");
        //ajax_page();
        // console.log(priStoneWeightArr[0]);
        // console.log(priStoneWeightArr[1]);
		
		ChangeState(0,0);
       
    }
    //拖动中执行此方法
    function jSliderControl_move(){
        //重量
        $("#Slider1").parent().find(".jslider-value").each(function(index){
            var val_input = $(this).children("span").html();
            var substr_sl = '&nbsp;–&nbsp;';
            if(index==0){
                if(val_input.indexOf(substr_sl) >= 0){

                    val_input = val_input.split(substr_sl)[0];
                }
                $(".searchWeight_borderL").val(val_input);
            }else{

                if(val_input.indexOf(substr_sl) >= 0){
                    val_input = val_input.split(substr_sl)[1];
                }
                $(".searchWeight_borderR").val(val_input);
            }
        });
        //价格
        $("#Slider2").parent().find(".jslider-value").each(function(index){
            var val_input = $(this).children("span").html();
            var substr_sl = '&nbsp;–&nbsp;';
            if(index==0){
                if(val_input.indexOf(substr_sl) >= 0){
                    val_input = val_input.split(substr_sl)[0];
                }
                val_input = val_input.replace(/,/g,'');
                $(".searchPrice_borderL").val(val_input);
            }else{
                if(val_input.indexOf(substr_sl) >= 0){
                    val_input = val_input.split(substr_sl)[1];
                }
                val_input = val_input.replace(/,/g,'');
                $(".searchPrice_borderR").val(val_input);
            }
        });
    }
   