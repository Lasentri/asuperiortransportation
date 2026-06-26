/* A Superior Transportation - app.js v3.0.1 */
'use strict';
var stMap,stPickupAC,stDropoffAC,stPickupMarker,stDropoffMarker,stRouteRenderer;
var stPickupLatLng=null,stDropoffLatLng=null,stActiveField='pickup';
var stCalcFare=0,stCalcMiles=0,stDiscountAmt=0,stFinalFare=0;
var squareCard=null,squarePayments=null;

/* Load Square SDK immediately so it is ready before user hits Pay */
(function(){
    if(window.ST&&ST.sqAppId){
        var sq=document.createElement('script');
        sq.src='https://web.squarecdn.com/v1/square.js';
        document.head.appendChild(sq);
    }
})();

function stInitMap(){
    var mapEl=document.getElementById('st-map');
    var placesMapEl=document.getElementById('st-places-map');
    if(mapEl){
        var center={lat:47.1211,lng:-88.5694};
        stMap=new google.maps.Map(mapEl,{center:center,zoom:13,gestureHandling:'greedy',mapTypeControl:false,streetViewControl:false,fullscreenControl:true,zoomControl:true,styles:[{featureType:'poi',elementType:'labels',stylers:[{visibility:'off'}]}]});
        stRouteRenderer=new google.maps.DirectionsRenderer({map:stMap,suppressMarkers:true,polylineOptions:{strokeColor:'#2e7d32',strokeWeight:5,strokeOpacity:0.8}});
        stMap.addListener('click',function(e){stReverseGeocode(e.latLng);});
        var pickupInput=document.getElementById('st-pickup');
        var dropoffInput=document.getElementById('st-dropoff');
        if(pickupInput){
            stPickupAC=new google.maps.places.Autocomplete(pickupInput,{componentRestrictions:{country:'us'},fields:['geometry','formatted_address','name']});
            stPickupAC.addListener('place_changed',function(){var p=stPickupAC.getPlace();if(p.geometry){stPickupLatLng=p.geometry.location;stPlaceMarker('pickup',stPickupLatLng,p.formatted_address||p.name);stMap.panTo(stPickupLatLng);stTryRoute();setTimeout(function(){if(dropoffInput){dropoffInput.focus();stActiveField='dropoff';}},50);}});
            pickupInput.addEventListener('focus',function(){stActiveField='pickup';});
        }
        if(dropoffInput){
            stDropoffAC=new google.maps.places.Autocomplete(dropoffInput,{componentRestrictions:{country:'us'},fields:['geometry','formatted_address','name']});
            stDropoffAC.addListener('place_changed',function(){var p=stDropoffAC.getPlace();if(p.geometry){stDropoffLatLng=p.geometry.location;stPlaceMarker('dropoff',stDropoffLatLng,p.formatted_address||p.name);stTryRoute();}});
            dropoffInput.addEventListener('focus',function(){stActiveField='dropoff';});
        }
    }
    if(placesMapEl&&window.stPlacesData){
        var pMap=new google.maps.Map(placesMapEl,{center:{lat:47.25,lng:-88.35},zoom:10,gestureHandling:'greedy',mapTypeControl:false,streetViewControl:false});
        var infoWin=new google.maps.InfoWindow();
        stPlacesData.forEach(function(p){
            var marker=new google.maps.Marker({map:pMap,position:{lat:p.lat,lng:p.lng},title:p.name,icon:{url:'https://maps.google.com/mapfiles/ms/icons/green-dot.png'}});
            marker.addListener('click',function(){infoWin.setContent('<div style="max-width:220px"><strong>'+p.name+'</strong><br><span style="font-size:12px">'+p.tag+'</span><br><p style="margin:6px 0 8px;font-size:13px">'+p.desc+'</p><a href="/#booking" style="color:#2e7d32;font-weight:600">Book Ride Here</a></div>');infoWin.open(pMap,marker);});
        });
    }
    stInitGasChart();
}

function stPlaceMarker(type,latLng,label){
    var isPickup=(type==='pickup');
    var existing=isPickup?stPickupMarker:stDropoffMarker;
    if(existing) existing.setMap(null);
    var marker=new google.maps.Marker({map:stMap,position:latLng,draggable:true,title:label,icon:{url:isPickup?'https://maps.google.com/mapfiles/ms/icons/green-dot.png':'https://maps.google.com/mapfiles/ms/icons/red-dot.png'}});
    marker.addListener('dragend',function(e){stReverseGeocode(e.latLng,type);});
    if(isPickup) stPickupMarker=marker; else stDropoffMarker=marker;
}

function stReverseGeocode(latLng,forceField){
    var gc=new google.maps.Geocoder();
    gc.geocode({location:latLng},function(results,status){
        if(status==='OK'&&results[0]){
            var addr=results[0].formatted_address;
            var field=forceField||stActiveField;
            var inp=document.getElementById(field==='pickup'?'st-pickup':'st-dropoff');
            if(inp){inp.value=addr;inp.dispatchEvent(new Event('input'));}
            if(field==='pickup'){stPickupLatLng=latLng;stPlaceMarker('pickup',latLng,addr);}
            else{stDropoffLatLng=latLng;stPlaceMarker('dropoff',latLng,addr);}
            stTryRoute();
        }
    });
}

function stTryRoute(){
    if(!stPickupLatLng||!stDropoffLatLng) return;
    var svc=new google.maps.DirectionsService();
    svc.route({origin:stPickupLatLng,destination:stDropoffLatLng,travelMode:google.maps.TravelMode.DRIVING},function(result,status){
        if(status==='OK'){
            stRouteRenderer.setDirections(result);
            var leg=result.routes[0].legs[0];
            var miles=leg.distance.value/1609.344;
            stCalcMiles=miles;
            stUpdateFare(miles);
            stMap.fitBounds(result.routes[0].bounds);
        }
    });
}

function stUpdateFare(miles){
    var s=window.ST||{};
    var flatMiles=parseFloat(s.flatMiles||5),flatPrice=parseFloat(s.flatPrice||10),perMile=parseFloat(s.perMile||2.50),baseRate=parseFloat(s.baseRate||3.00);
    var fare=miles<=flatMiles?flatPrice:baseRate+(miles*perMile);
    stCalcFare=fare;stFinalFare=fare;stDiscountAmt=0;
    var milesEl=document.getElementById('st-fare-miles'),amountEl=document.getElementById('st-fare-amount'),fareBox=document.getElementById('st-fare-box');
    if(milesEl) milesEl.textContent=miles.toFixed(1)+' mi';
    if(amountEl) amountEl.textContent='$'+fare.toFixed(2);
    if(fareBox) fareBox.style.display='block';
    stSyncTotals();
}

function stSyncTotals(){
    var subEl=document.getElementById('st-total-sub'),discEl=document.getElementById('st-total-discount'),finalEl=document.getElementById('st-total-final'),discRow=document.getElementById('st-discount-row'),sumMile=document.getElementById('st-sum-miles'),sumFare=document.getElementById('st-sum-fare');
    if(subEl) subEl.textContent='$'+stCalcFare.toFixed(2);
    if(finalEl) finalEl.textContent='$'+stFinalFare.toFixed(2);
    if(discRow) discRow.style.display=stDiscountAmt>0?'flex':'none';
    if(discEl) discEl.textContent='-$'+stDiscountAmt.toFixed(2);
    if(sumMile) sumMile.textContent=stCalcMiles.toFixed(1)+' mi';
    if(sumFare) sumFare.textContent='$'+stFinalFare.toFixed(2);
}

async function stShowPaymentPopup(){
    var overlay=document.createElement('div');
    overlay.id='st-pay-overlay';
    overlay.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
    var modal=document.createElement('div');
    modal.style.cssText='background:#122812;border:2px solid #c8a84b;border-radius:10px;padding:28px;width:100%;max-width:400px;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.6);';
    modal.innerHTML='<h3 style="font-family:Oswald,sans-serif;color:#c8a84b;margin:0 0 6px;font-size:1.2rem;letter-spacing:.06em">CARD PAYMENT</h3>'
        +'<div style="color:rgba(255,255,255,.5);font-size:.85rem;margin-bottom:16px">Total due: <strong style="color:#f5c518;font-size:1.15rem" id="st-popup-total">$0.00</strong></div>'
        +'<div id="st-popup-card" style="background:#fff;border-radius:6px;padding:10px;min-height:50px;margin-bottom:14px"></div>'
        +'<div id="st-popup-error" style="color:#ef9a9a;font-size:.82rem;margin-bottom:10px;display:none;background:rgba(198,40,40,.2);padding:8px 12px;border-radius:4px;"></div>'
        +'<div style="display:flex;gap:10px;margin-top:4px">'
        +'<button id="st-popup-cancel" style="flex:1;padding:11px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.7);border-radius:6px;cursor:pointer;font-size:.88rem">Cancel</button>'
        +'<button id="st-popup-pay" style="flex:2;padding:11px;background:#c8a84b;border:none;color:#0f2a0f;border-radius:6px;cursor:pointer;font-weight:700;font-size:.95rem;font-family:Oswald,sans-serif;letter-spacing:.05em">PAY NOW</button>'
        +'</div>';
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    var tot=document.getElementById('st-popup-total');
    if(tot) tot.textContent='$'+stFinalFare.toFixed(2);
    function closePopup(){if(squareCard){try{squareCard.destroy();}catch(e){} squareCard=null;} squarePayments=null; overlay.remove();}
    overlay.addEventListener('click',function(e){if(e.target===overlay) closePopup();});
    document.getElementById('st-popup-cancel').addEventListener('click',closePopup);
    async function initCard(){
        if(typeof Square==='undefined'){setTimeout(initCard,300);return;}
        try{
            squarePayments=await Square.payments(ST.sqAppId, ST.sqLocationId);
            squareCard=await squarePayments.card({style:{'.input-container':{borderColor:'#ccc',borderRadius:'4px'},'input':{color:'#000','font-size':'16px'}}});
            await squareCard.attach('#st-popup-card');
        }catch(e){
            var err=document.getElementById('st-popup-error');
            if(err){err.textContent='Card form failed to load. Call 906-370-4094 to pay.';err.style.display='block';}
            console.error('Square initCard error:',e);
        }
    }
    initCard();
    document.getElementById('st-popup-pay').addEventListener('click',async function(){
        var btn=this,errEl=document.getElementById('st-popup-error');
        btn.disabled=true;btn.textContent='Processing...';
        try{
            var result=await squareCard.tokenize();
            if(result.status!=='OK'){
                if(errEl){errEl.textContent='Card error: '+(result.errors?result.errors[0].message:'Please try again');errEl.style.display='block';}
                btn.disabled=false;btn.textContent='PAY NOW';return;
            }
            var cd=new FormData();
            cd.append('action','st_charge_square');cd.append('nonce',ST.nonce);
            cd.append('token',result.token);cd.append('amount',Math.round(stFinalFare*100));
            cd.append('note','Ride booking - A Superior Transportation');
            var cr=await fetch(ST.ajax,{method:'POST',body:cd});
            var cj=await cr.json();
            if(!cj.success){
                if(errEl){errEl.textContent='Payment failed: '+(cj.data?cj.data.message:'Please call us');errEl.style.display='block';}
                btn.disabled=false;btn.textContent='PAY NOW';return;
            }
            closePopup();
            stSubmitBooking(cj.data.payment_id||'');
        }catch(e){
            if(errEl){errEl.textContent='Error processing payment. Call '+ST.phone;errEl.style.display='block';}
            btn.disabled=false;btn.textContent='PAY NOW';
        }
    });
}
function stInitSquare(){}

function stSubmitBooking(paymentId){
    var errEl=document.getElementById('st-form-error-3');
    var fd=new FormData();
    fd.append('action','st_book_ride');fd.append('nonce',ST.nonce);
    fd.append('name',(document.getElementById('st-name')||{}).value||'');
    fd.append('phone',(document.getElementById('st-phone')||{}).value||'');
    fd.append('email',(document.getElementById('st-email')||{}).value||'');
    fd.append('pickup',(document.getElementById('st-pickup')||{}).value||'');
    fd.append('dropoff',(document.getElementById('st-dropoff')||{}).value||'');
    fd.append('date',(document.getElementById('st-date')||{}).value||'');
    fd.append('time',(document.getElementById('st-time')||{}).value||'');
    fd.append('passengers',(document.getElementById('st-passengers')||{}).value||1);
    fd.append('notes',(document.getElementById('st-notes')||{}).value||'');
    fd.append('distance',stCalcMiles.toFixed(2));
    fd.append('fare',stFinalFare.toFixed(2));
    fd.append('coupon',(document.getElementById('st-coupon')||{}).value||'');
    fd.append('payment_id',paymentId);
    fetch(ST.ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){var msg=document.getElementById('st-success-msg');if(msg)msg.textContent=d.data.message||'Booking confirmed.';showStep(4);}
        else{if(errEl){errEl.textContent=(d.data&&d.data.message)||'Booking failed. Please call us.';errEl.style.display='block';}}
    }).catch(function(){if(errEl){errEl.textContent='Network error. Please call '+ST.phone;errEl.style.display='block';}});
}

document.addEventListener('DOMContentLoaded',function(){
    var calBtn=document.getElementById('st-show-calendar'),calWrap=document.getElementById('st-cal-wrap');
    if(calBtn&&calWrap){calBtn.addEventListener('click',function(e){e.preventDefault();calWrap.style.display=calWrap.style.display==='none'?'block':'none';calBtn.textContent=calWrap.style.display==='none'?'View open times below':'Hide calendar';});}

    document.querySelectorAll('input[name="payment_method"]').forEach(function(r){r.addEventListener('change',function(){var cw=document.getElementById('st-card-wrap');if(cw) cw.style.display=this.value==='card'?'block':'none';});});

    var locBtn=document.getElementById('st-locate-me');
    if(locBtn){locBtn.addEventListener('click',function(){if(!navigator.geolocation){alert('Geolocation not supported.');return;}locBtn.textContent='\u231b';navigator.geolocation.getCurrentPosition(function(pos){locBtn.textContent='\ud83d\udccd';var ll=new google.maps.LatLng(pos.coords.latitude,pos.coords.longitude);stReverseGeocode(ll,'pickup');if(stMap) stMap.panTo(ll);},function(){locBtn.textContent='\ud83d\udccd';alert('Could not get location.');});});}

    var couponBtn=document.getElementById('st-apply-coupon');
    if(couponBtn){couponBtn.addEventListener('click',function(){var code=(document.getElementById('st-coupon')||{}).value||'';var msg=document.getElementById('st-coupon-msg');if(!code.trim()){if(msg) msg.textContent='Enter a coupon code.';return;}var fd=new FormData();fd.append('action','st_check_coupon');fd.append('nonce',ST.nonce);fd.append('code',code);fd.append('fare',stCalcFare);fetch(ST.ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(msg){msg.textContent=d.msg||'';msg.style.color=d.valid?'green':'red';}if(d.valid){stDiscountAmt=d.discount;stFinalFare=d.new_fare;stSyncTotals();}});});}

    function stClosePac(){document.querySelectorAll('.pac-container').forEach(function(el){el.style.display='none';});document.activeElement&&document.activeElement.blur();}
    function showStep(n){stClosePac();for(var i=1;i<=4;i++){var s=document.getElementById('step-'+i);if(s) s.style.display=i===n?'block':'none';var ind=document.getElementById('step-ind-'+i);if(ind){ind.classList.remove('active','done');if(i===n) ind.classList.add('active');else if(i<n) ind.classList.add('done');}}}

    var next1=document.getElementById('st-next-1');
    if(next1){next1.addEventListener('click',function(){var date=(document.getElementById('st-date')||{}).value||'',time=(document.getElementById('st-time')||{}).value||'',pickup=(document.getElementById('st-pickup')||{}).value||'',dropoff=(document.getElementById('st-dropoff')||{}).value||'',errEl=document.getElementById('st-form-error-1');if(!date||!time||!pickup||!dropoff){if(errEl){errEl.textContent='Please fill in date, time, pickup and dropoff.';errEl.style.display='block';}return;}if(!stCalcFare){if(errEl){errEl.textContent='Please wait while we calculate your route...';errEl.style.display='block';}var gc=new google.maps.Geocoder();gc.geocode({address:pickup+', Michigan, USA'},function(r1,s1){if(s1==='OK'&&r1[0]){stPickupLatLng=r1[0].geometry.location;stPlaceMarker('pickup',stPickupLatLng,pickup);gc.geocode({address:dropoff+', Michigan, USA'},function(r2,s2){if(s2==='OK'&&r2[0]){stDropoffLatLng=r2[0].geometry.location;stPlaceMarker('dropoff',stDropoffLatLng,dropoff);stTryRoute();setTimeout(function(){if(errEl) errEl.style.display='none';showStep(2);},1500);}});}});return;}if(errEl) errEl.style.display='none';showStep(2);});}

    var next2=document.getElementById('st-next-2');
    if(next2){next2.addEventListener('click',function(){var name=(document.getElementById('st-name')||{}).value||'',phone=(document.getElementById('st-phone')||{}).value||'',errEl=document.getElementById('st-form-error-2');if(!name.trim()||!phone.trim()){if(errEl){errEl.textContent='Please enter your name and phone number.';errEl.style.display='block';}return;}if(errEl) errEl.style.display='none';stSyncTotals();showStep(3);});}

    var back2=document.getElementById('st-back-2'); if(back2) back2.addEventListener('click',function(){showStep(1);});
    var back3=document.getElementById('st-back-3'); if(back3) back3.addEventListener('click',function(){showStep(2);});

    var confirmBtn=document.getElementById('st-confirm-btn');
    if(confirmBtn){confirmBtn.addEventListener('click',function(){
        var payMethod=(document.querySelector('input[name="payment_method"]:checked')||{}).value||'cash';
        if(payMethod==='card'){stShowPaymentPopup();}
        else{stSubmitBooking('');}
    });}

    document.querySelectorAll('.st-place-book').forEach(function(btn){btn.addEventListener('click',function(){var place=btn.getAttribute('data-place');if(place) sessionStorage.setItem('st_dropoff_preset',place);});});
    var preset=sessionStorage.getItem('st_dropoff_preset');if(preset){var di=document.getElementById('st-dropoff');if(di){di.value=preset;sessionStorage.removeItem('st_dropoff_preset');}}

    var ham=document.getElementById('st-hamburger'),links=document.getElementById('st-nav-links');
    if(ham&&links){ham.addEventListener('click',function(){var open=links.classList.toggle('st-nav-open');ham.setAttribute('aria-expanded',open?'true':'false');});document.addEventListener('click',function(e){if(!ham.contains(e.target)&&!links.contains(e.target)){links.classList.remove('st-nav-open');ham.setAttribute('aria-expanded','false');}});}

    var nav=document.getElementById('st-nav');if(nav){window.addEventListener('scroll',function(){nav.classList.toggle('st-nav-scrolled',window.scrollY>10);},{passive:true});}
});

function stInitGasChart(){
    if(!window.stGasData||!document.getElementById('st-gas-chart')) return;
    var labels=[],prices=[],months=window.stGasMonths||['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    Object.keys(window.stGasData).sort().forEach(function(yr){var pp=window.stGasData[yr];for(var m=1;m<=12;m++){if(pp[m]){labels.push(months[m]+' '+yr);prices.push(pp[m]);}}});
    if(!labels.length) return;
    if(typeof Chart==='undefined'){var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';s.onload=function(){stDrawGasChart(labels,prices);};document.head.appendChild(s);}
    else{stDrawGasChart(labels,prices);}
}

function stDrawGasChart(labels,prices){
    var ctx=document.getElementById('st-gas-chart');if(!ctx) return;
    new Chart(ctx,{type:'line',data:{labels:labels,datasets:[{label:'Gas Price ($/gal)',data:prices,borderColor:'#2e7d32',backgroundColor:'rgba(46,125,50,0.1)',borderWidth:2,pointRadius:4,pointBackgroundColor:'#2e7d32',tension:0.3,fill:true}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return '$'+c.parsed.y.toFixed(3);}}}},scales:{y:{ticks:{callback:function(v){return '$'+v.toFixed(2);}}}}}});
}
