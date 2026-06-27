/* A Superior Transportation - app.js v3.2.6 */
'use strict';
var stMap,stPickupAC,stDropoffAC,stPickupMarker,stDropoffMarker,stRouteRenderer;
var stPickupLatLng=null,stDropoffLatLng=null,stActiveField='pickup';
var stCalcFare=0,stCalcMiles=0,stDiscountAmt=0,stFinalFare=0;
var squareCard=null,squarePayments=null;

/* Load Square SDK immediately */
(function(){
    if(window.ST&&ST.sqAppId){
        var sq=document.createElement('script');
        sq.src='https://web.squarecdn.com/v1/square.js';
        document.head.appendChild(sq);
    }
})();

/* Load flat rates from server */
var stFlatRates = [];
var stActiveFlatRate = null;
(function(){
    fetch(window.ST ? ST.ajax : '', {
        method:'POST',
        body: new URLSearchParams({action:'st_get_flat_rates'})
    }).then(function(r){return r.json();}).then(function(d){
        if(d.success) stFlatRates = d.data;
    }).catch(function(){});
})();

function stMatchFlatRate(dropoff){
    if(!dropoff || !stFlatRates.length) return null;
    var lower = dropoff.toLowerCase();
    for(var i=0;i<stFlatRates.length;i++){
        var fr = stFlatRates[i];
        if(lower.indexOf(fr.name.toLowerCase()) !== -1 ||
           lower.indexOf((fr.address||'').toLowerCase()) !== -1){
            return fr;
        }
    }
    return null;
}

function stApplyFlatRate(fr){
    stActiveFlatRate = fr;
    var passengers = parseInt((document.getElementById('st-passengers')||{}).value||1);
    var price = passengers >= 3 ? fr.price * 1.40 : fr.price;
    stCalcFare = price; stFinalFare = price; stDiscountAmt = 0;
    var milesEl=document.getElementById('st-fare-miles'),
        amountEl=document.getElementById('st-fare-amount'),
        fareBox=document.getElementById('st-fare-box');
    if(milesEl) milesEl.textContent = 'Flat Rate';
    if(amountEl) amountEl.textContent = '$'+price.toFixed(2);
    if(fareBox){
        fareBox.style.display='block';
        var badge = fareBox.querySelector('.st-flat-rate-badge');
        if(!badge){
            badge = document.createElement('div');
            badge.className = 'st-flat-rate-badge';
            badge.style.cssText='font-size:.75rem;color:#f5c518;margin-top:4px;font-style:italic;';
            fareBox.appendChild(badge);
        }
        badge.textContent = passengers >= 3
            ? '\ud83d\udc65 ' + passengers + ' passengers: base $'+fr.price.toFixed(2)+' + 40% = $'+price.toFixed(2)
            : '\u2605 Fixed rate: '+fr.name+(passengers>=3?' (3+ pax surcharge applied)':'');
    }
    stSyncTotals();
    /* Show exact address field */
    var exactWrap = document.getElementById('st-exact-address-wrap');
    if(exactWrap){
        exactWrap.style.display = 'block';
        var exactInput = document.getElementById('st-dropoff-exact');
        if(exactInput){ exactInput.value=''; setTimeout(function(){ exactInput.focus(); }, 100); }
    }
}

function stClearFlatRate(){
    stActiveFlatRate = null;
    var fareBox=document.getElementById('st-fare-box');
    if(fareBox){ var badge=fareBox.querySelector('.st-flat-rate-badge'); if(badge) badge.remove(); }
    var exactWrap=document.getElementById('st-exact-address-wrap');
    if(exactWrap){ exactWrap.style.display='none'; var ei=document.getElementById('st-dropoff-exact'); if(ei) ei.value=''; }
}


function stClosePac(){
    document.querySelectorAll('.pac-container').forEach(function(el){
        el.style.display='none';
        el.style.visibility='hidden';
        el.style.opacity='0';
    });
}

/* 3-step flow: 1=Ride Details, 2=Contact+Book, 3=Done */

function stBookMidnightFlight(e){
    if(e) e.preventDefault();

    /* Scroll to booking form */
    var booking = document.getElementById('booking');
    if(booking) booking.scrollIntoView({behavior:'smooth', block:'start'});

    setTimeout(function(){
        /* Set date to today */
        var dateEl = document.getElementById('st-date');
        if(dateEl){
            var today = new Date();
            var mm = String(today.getMonth()+1).padStart(2,'0');
            var dd = String(today.getDate()).padStart(2,'0');
            var yyyy = today.getFullYear();
            dateEl.value = yyyy+'-'+mm+'-'+dd;
        }

        /* Set time - find closest available option to 11:59 PM */
        var timeEl = document.getElementById('st-time');
        if(timeEl){
            /* Try 23:45 first (last 15-min slot before midnight) */
            var options = timeEl.options;
            var bestVal = '';
            for(var i=0;i<options.length;i++){
                if(options[i].value >= '23:00') bestVal = options[i].value;
            }
            if(bestVal) timeEl.value = bestVal;
        }

        /* Set PICKUP to CMX Airport */
        var pickupEl = document.getElementById('st-pickup');
        if(pickupEl){
            pickupEl.value = 'Houghton County Memorial Airport (CMX), 23810 Airpark Blvd, Calumet, MI 49913';
            stShowFlatRateHint();
        }

        /* Clear any previous dropoff/fare state */
        var dropoffEl = document.getElementById('st-dropoff');
        if(dropoffEl) dropoffEl.value = '';
        stClearFlatRate();
        stCalcFare = 0; stFinalFare = 0; stCalcMiles = 0;
        var fareBox = document.getElementById('st-fare-box');
        if(fareBox) fareBox.style.display = 'none';

        /* Hide exact address wrap until they pick a destination */
        var exactWrap = document.getElementById('st-exact-address-wrap');
        if(exactWrap) exactWrap.style.display = 'none';

        /* Open flat rate popup for destination selection */
        setTimeout(function(){
            stOpenFlatRatePopup();
        }, 300);

    }, 500);
}

function showStep(n){
    stClosePac();
    if(document.activeElement&&document.activeElement.blur) document.activeElement.blur();
    for(var i=1;i<=3;i++){
        var s=document.getElementById('step-'+i);
        if(s) s.style.display=i===n?'block':'none';
        var ind=document.getElementById('step-ind-'+i);
        if(ind){
            ind.classList.remove('active','done');
            if(i===n) ind.classList.add('active');
            else if(i<n) ind.classList.add('done');
        }
    }
}

function stInitMap(){
    var mapEl=document.getElementById('st-map');
    var placesMapEl=document.getElementById('st-places-map');
    if(mapEl){
        var center={lat:47.1211,lng:-88.5694};
        stMap=new google.maps.Map(mapEl,{center:center,zoom:13,gestureHandling:'greedy',mapTypeControl:false,streetViewControl:false,fullscreenControl:true,zoomControl:true,styles:[{featureType:'poi',elementType:'labels',stylers:[{visibility:'off'}]}]});
        stRouteRenderer=new google.maps.DirectionsRenderer({map:stMap,suppressMarkers:true,polylineOptions:{strokeColor:'#2e7d32',strokeWeight:5,strokeOpacity:0.8}});
        stMap.addListener('click',function(e){
            stClosePac();
            var exactWrap=document.getElementById('st-exact-address-wrap');
            if(exactWrap && exactWrap.style.display !== 'none'){
                stReverseGeocodeExact(e.latLng);
            } else {
                stReverseGeocode(e.latLng);
            }
        });

        /* Auto-detect location on load for pickup */
        if(navigator.geolocation && pickupInput && !pickupInput.value){
            navigator.geolocation.getCurrentPosition(function(pos){
                var ll=new google.maps.LatLng(pos.coords.latitude,pos.coords.longitude);
                stReverseGeocode(ll,'pickup');
                stMap.panTo(ll);
            }, function(){
                /* Silently fail - user can type or use the pin button */
            }, {timeout:5000, maximumAge:60000});
        }
        var pickupInput=document.getElementById('st-pickup');
        var dropoffInput=document.getElementById('st-dropoff');
        if(pickupInput){
            stPickupAC=new google.maps.places.Autocomplete(pickupInput,{componentRestrictions:{country:'us'},fields:['geometry','formatted_address','name']});
            stPickupAC.addListener('place_changed',function(){
                var p=stPickupAC.getPlace();
                if(p&&p.geometry){
                    stPickupLatLng=p.geometry.location;
                    stPlaceMarker('pickup',stPickupLatLng,p.formatted_address||p.name);
                    stMap.panTo(stPickupLatLng);
                    stTryRoute();
                    /* Force close PAC immediately */
                    if(pickupInput) pickupInput.blur();
                    stClosePac();
                    setTimeout(function(){
                        stClosePac();
                        if(dropoffInput){dropoffInput.focus();stActiveField='dropoff';}
                        stShowFlatRateHint();
                    },150);
                }
            });
            pickupInput.addEventListener('focus',function(){stActiveField='pickup';});
        }
        if(dropoffInput){
            stDropoffAC=new google.maps.places.Autocomplete(dropoffInput,{componentRestrictions:{country:'us'},fields:['geometry','formatted_address','name']});
            stDropoffAC.addListener('place_changed',function(){
                var p=stDropoffAC.getPlace();
                if(p&&p.geometry){
                    stDropoffLatLng=p.geometry.location;
                    stPlaceMarker('dropoff',stDropoffLatLng,p.formatted_address||p.name);
                    if(dropoffInput) dropoffInput.blur();
                    stClosePac();
                    var fr=stMatchFlatRate(p.formatted_address||p.name||'');
                    if(fr){ stApplyFlatRate(fr); }
                    else { stClearFlatRate(); stTryRoute(); }
                }
            });
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

function stReverseGeocodeExact(latLng){
    var gc=new google.maps.Geocoder();
    gc.geocode({location:latLng},function(results,status){
        if(status==='OK'&&results[0]){
            var exactEl=document.getElementById('st-dropoff-exact');
            if(exactEl){
                exactEl.value=results[0].formatted_address;
                exactEl.style.borderColor='#81c784';
                setTimeout(function(){exactEl.style.borderColor='#c8a84b';},1000);
            }
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
    var sumMile=document.getElementById('st-sum-miles'),sumFare=document.getElementById('st-sum-fare');
    if(sumMile) sumMile.textContent=stCalcMiles.toFixed(1)+' mi';
    if(sumFare) sumFare.textContent='$'+stFinalFare.toFixed(2);
}

async function stShowPaymentPopup(){
    var overlay=document.createElement('div');
    overlay.id='st-pay-overlay';
    overlay.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
    var modal=document.createElement('div');
    modal.style.cssText='background:#122812;border:2px solid #c8a84b;border-radius:10px;padding:28px;width:100%;max-width:420px;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.6);';

    modal.innerHTML='<h3 style="font-family:Oswald,sans-serif;color:#c8a84b;margin:0 0 6px;font-size:1.2rem;letter-spacing:.06em">CARD PAYMENT</h3>'
        +'<div style="color:rgba(255,255,255,.5);font-size:.85rem;margin-bottom:16px">Total due: <strong style="color:#f5c518;font-size:1.15rem" id="st-popup-total">$0.00</strong></div>'
        +'<div id="st-popup-card" style="background:#fff;border-radius:6px;padding:10px;min-height:50px;margin-bottom:14px"></div>'
        +'<div id="st-popup-fallback" style="display:none;background:rgba(200,168,75,.12);border:1px solid #c8a84b;border-radius:8px;padding:18px;margin-bottom:14px;text-align:center;">'
        +'<div style="font-size:1.8rem;margin-bottom:8px">\ud83d\udcc5</div>'
        +'<div style="color:#f5c518;font-family:Oswald,sans-serif;font-size:1rem;letter-spacing:.05em;margin-bottom:8px">BOOKING CONFIRMED</div>'
        +'<div style="color:rgba(255,255,255,.8);font-size:.88rem;line-height:1.6;">Your ride is reserved. Our dispatcher will contact you shortly with a secure payment link via text or email.</div>'
        +'<div style="margin-top:12px;color:rgba(255,255,255,.5);font-size:.78rem;">Questions? Call <a href="tel:9063700094" style="color:#c8a84b;font-weight:700;">906-370-4094</a></div>'
        +'</div>'
        +'<div id="st-popup-error" style="color:#ef9a9a;font-size:.82rem;margin-bottom:10px;display:none;background:rgba(198,40,40,.2);padding:8px 12px;border-radius:4px;"></div>'
        +'<div id="st-popup-btns" style="display:flex;gap:10px;margin-top:4px">'
        +'<button id="st-popup-cancel" style="flex:1;padding:11px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.7);border-radius:6px;cursor:pointer;font-size:.88rem">Thank You! See You Soon!</button>'
        +'<button id="st-popup-pay" style="flex:2;padding:11px;background:#c8a84b;border:none;color:#0f2a0f;border-radius:6px;cursor:pointer;font-weight:700;font-size:.95rem;font-family:Oswald,sans-serif;letter-spacing:.05em">PAY NOW</button>'
        +'</div>';

    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    var tot=document.getElementById('st-popup-total');
    if(tot) tot.textContent='$'+stFinalFare.toFixed(2);

    function closePopup(){if(squareCard){try{squareCard.destroy();}catch(e){} squareCard=null;} squarePayments=null; overlay.remove();}

    function showFallback(){
        var cardEl=document.getElementById('st-popup-card');
        var fallEl=document.getElementById('st-popup-fallback');
        var payBtn=document.getElementById('st-popup-pay');
        if(cardEl) cardEl.style.display='none';
        if(fallEl) fallEl.style.display='block';
        if(payBtn) payBtn.style.display='none';
        stSubmitBooking('CARD_PENDING');
    }

    overlay.addEventListener('click',function(e){if(e.target===overlay) closePopup();});
    document.getElementById('st-popup-cancel').addEventListener('click',function(){closePopup();showStep(3);});

    async function initCard(){
        if(typeof Square==='undefined'){setTimeout(initCard,300);return;}
        try{
            squarePayments=await Square.payments(ST.sqAppId, ST.sqLocationId);
            squareCard=await squarePayments.card({style:{'.input-container':{borderColor:'#ccc',borderRadius:'4px'},'input':{color:'#000','font-size':'16px'}}});
            await squareCard.attach('#st-popup-card');
        }catch(e){
            console.error('Square initCard error:',e);
            showFallback();
        }
    }

    var squareTimeout=setTimeout(function(){if(!squareCard) showFallback();},4000);
    initCard().then(function(){clearTimeout(squareTimeout);}).catch(function(){clearTimeout(squareTimeout);showFallback();});

    document.getElementById('st-popup-pay').addEventListener('click',async function(){
        var btn=this,errEl=document.getElementById('st-popup-error');
        if(!squareCard){showFallback();return;}
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
            console.error('Square pay error:',e);
            showFallback();
        }
    });
}
function stInitSquare(){}

function stSubmitBooking(paymentId){
    var errEl=document.getElementById('st-form-error-2');
    var fd=new FormData();
    fd.append('action','st_book_ride');fd.append('nonce',ST.nonce);
    fd.append('name',(document.getElementById('st-name')||{}).value||'');
    fd.append('phone',(document.getElementById('st-phone')||{}).value||'');
    fd.append('email',(document.getElementById('st-email')||{}).value||'');
    fd.append('pickup',(document.getElementById('st-pickup')||{}).value||'');
    /* Use exact address if flat rate selected, otherwise standard dropoff */
    var dropoffVal = (document.getElementById('st-dropoff')||{}).value||'';
    var exactVal = (document.getElementById('st-dropoff-exact')||{}).value||'';
    if(stActiveFlatRate && exactVal.trim()){
        dropoffVal = exactVal.trim() + ' [Flat Rate Zone: ' + stActiveFlatRate.name + ']';
    }
    fd.append('dropoff', dropoffVal);
    fd.append('date',(document.getElementById('st-date')||{}).value||'');
    fd.append('time',(document.getElementById('st-time')||{}).value||'');
    fd.append('passengers',(document.getElementById('st-passengers')||{}).value||1);
    fd.append('notes',(document.getElementById('st-notes')||{}).value||'');
    fd.append('distance',stCalcMiles.toFixed(2));
    fd.append('fare',stFinalFare.toFixed(2));
    fd.append('coupon',(document.getElementById('st-coupon')||{}).value||'');
    fd.append('payment_id',paymentId);
    fetch(ST.ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){
            if(paymentId!=='CARD_PENDING'){
                var msg=document.getElementById('st-success-msg');
                if(msg) msg.textContent=d.data.message||'Booking confirmed.';
                showStep(3);
            }
        } else {
            if(errEl){errEl.textContent=(d.data&&d.data.message)||'Booking failed. Please call us.';errEl.style.display='block';}
        }
    }).catch(function(){if(errEl){errEl.textContent='Network error. Please call '+ST.phone;errEl.style.display='block';}});
}


/* -------------------------------------------------
   FLAT RATE DESTINATION POPUP
------------------------------------------------- */
var stFlatRatePopupOpen = false;

/* Houghton/Hancock/CMX pickup keywords */
var ST_HH_KEYWORDS = ['houghton','hancock','cmx','airpark','keweenaw','portage'];

function stPickupIsHoughtonArea(){
    var val = (document.getElementById('st-pickup')||{}).value||'';
    val = val.toLowerCase();
    for(var i=0;i<ST_HH_KEYWORDS.length;i++){
        if(val.indexOf(ST_HH_KEYWORDS[i])!==-1) return true;
    }
    return false;
}

function stShowFlatRateHint(){
    var hint=document.getElementById('st-flatrate-hint');
    if(hint) hint.style.display = stPickupIsHoughtonArea() ? 'block' : 'none';
}

function stOpenFlatRatePopup(){
    if(stFlatRatePopupOpen) return;
    stFlatRatePopupOpen = true;

    /* Directions blocks - only north for now; placeholders for others */
    var blocks = {
        'north_bound': { label: 'North Bound', subtitle: 'Houghton to Copper Harbor via US-41', color: '#1a73e8' },
        'south_bound': { label: 'South Bound', subtitle: 'Coming Soon', color: '#888', disabled: true },
        'east_bound':  { label: 'East Bound', subtitle: 'Coming Soon', color: '#888', disabled: true },
        'west_bound':  { label: 'West Bound', subtitle: 'Houghton to Lake of the Clouds via M-26/US-45', color: '#2e7d32' },
    };

    var overlay = document.createElement('div');
    overlay.id = 'st-fr-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:10000;display:flex;align-items:center;justify-content:center;padding:16px;';

    var modal = document.createElement('div');
    modal.style.cssText = 'background:#0f2a0f;border:2px solid #c8a84b;border-radius:10px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 12px 48px rgba(0,0,0,.7);';

    /* Header */
    var header = '<div style="background:#1a3a1a;padding:18px 22px;border-bottom:1px solid rgba(200,168,75,.3);position:sticky;top:0;z-index:1;">'
        + '<div style="display:flex;justify-content:space-between;align-items:center">'
        + '<div><div style="font-family:Oswald,sans-serif;font-size:1.15rem;color:#c8a84b;letter-spacing:.06em;">?? FLAT RATE DESTINATIONS</div>'
        + '<div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:3px;">From Houghton · Hancock · CMX Airport</div></div>'
        + '<button id="st-fr-close" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:1.4rem;cursor:pointer;line-height:1;padding:4px 8px;">?</button>'
        + '</div></div>';

    /* Policy note */
    var policy = '<div style="margin:14px 18px;background:rgba(200,168,75,.1);border:1px solid rgba(200,168,75,.3);border-radius:6px;padding:10px 14px;font-size:.78rem;color:rgba(255,255,255,.7);line-height:1.6;">'
        + '? <strong style="color:#c8a84b">Pricing Policy:</strong> Rates shown are for <strong>1?2 passengers</strong>. '
        + '3 or more passengers: flat rate + <strong>40% surcharge</strong> (calculated below).'
        + '</div>';

    /* Passenger selector */
    var paxSel = '<div style="margin:0 18px 14px;display:flex;align-items:center;gap:10px;">'
        + '<label style="font-size:.8rem;color:rgba(255,255,255,.6);font-family:Oswald,sans-serif;letter-spacing:.06em;">PASSENGERS:</label>'
        + '<select id="st-fr-pax" style="background:#163016;border:1px solid rgba(200,168,75,.4);color:#fff;border-radius:4px;padding:6px 10px;font-size:.88rem;">'
        + '<option value="1">1 passenger</option><option value="2">2 passengers</option>'
        + '<option value="3">3 passengers</option><option value="4">4 passengers</option>'
        + '<option value="5">5 passengers</option><option value="6">6 passengers</option>'
        + '<option value="7">7 passengers</option><option value="8">8 passengers</option>'
        + '</select>'
        + '<span id="st-fr-pax-note" style="font-size:.75rem;color:#81c784;display:none;">+40% surcharge applied</span>'
        + '</div>';

    /* Direction tabs */
    var tabs = '<div style="display:flex;gap:6px;margin:0 18px 14px;flex-wrap:wrap;">';
    var firstActive = true;
    Object.keys(blocks).forEach(function(bk){
        var b = blocks[bk];
        var isActive = (bk === 'north_bound');
        tabs += '<button class="st-fr-tab" data-block="'+bk+'" '
            + (b.disabled ? 'disabled ' : '')
            + 'style="padding:7px 14px;border-radius:4px;border:1px solid '+(isActive?'#c8a84b':'rgba(255,255,255,.15)')+';'
            + 'background:'+(isActive?'rgba(200,168,75,.15)':'rgba(255,255,255,.04)')+';'
            + 'color:'+(isActive?'#c8a84b':(b.disabled?'#555':'rgba(255,255,255,.5)'))+';'
            + 'font-size:.78rem;font-weight:600;cursor:'+(b.disabled?'default':'pointer')+';font-family:Oswald,sans-serif;letter-spacing:.04em;">'
            + b.label + (b.disabled ? ' <span style="font-size:.65rem">(soon)</span>' : '')
            + '</button>';
    });
    tabs += '</div>';

    /* Destination list container */
    var listWrap = '<div id="st-fr-list" style="padding:0 18px 18px;"></div>';

    modal.innerHTML = header + policy + paxSel + tabs + listWrap;
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    /* Render destinations for a block */
    function renderBlock(blockKey){
        var listEl = document.getElementById('st-fr-list');
        var pax = parseInt(document.getElementById('st-fr-pax').value||1);
        var blockRates = stFlatRates.filter(function(r){ return r.block === blockKey; });
        if(!blockRates.length){
            listEl.innerHTML = '<div style="color:rgba(255,255,255,.4);text-align:center;padding:24px;font-style:italic;">No destinations configured yet.</div>';
            return;
        }
        var html = '<div style="display:flex;flex-direction:column;gap:6px;">';
        blockRates.forEach(function(fr){
            var price = pax >= 3 ? fr.price * 1.40 : fr.price;
            var paxLabel = pax >= 3
                ? '<span style="font-size:.7rem;color:#ffb74d;margin-left:6px;">+40%</span>'
                : '';
            html += '<button class="st-fr-dest" data-name="'+fr.name+'" data-address="'+fr.address+'" data-price="'+fr.price+'"'
                + ' style="display:flex;justify-content:space-between;align-items:center;'
                + 'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:6px;'
                + 'padding:11px 14px;cursor:pointer;text-align:left;transition:all .15s;width:100%;"'
                + ' onmouseover="this.style.background=\'rgba(200,168,75,.12)\';this.style.borderColor=\'#c8a84b\';"'
                + ' onmouseout="this.style.background=\'rgba(255,255,255,.04)\';this.style.borderColor=\'rgba(255,255,255,.1)\';">'
                + '<span style="color:#fff;font-size:.88rem;font-weight:600;">'+fr.name+'</span>'
                + '<span style="color:#f5c518;font-family:Oswald,sans-serif;font-size:1rem;font-weight:700;white-space:nowrap;">$'+price.toFixed(2)+paxLabel+'</span>'
                + '</button>';
        });
        html += '</div>';
        listEl.innerHTML = html;

        /* Destination click */
        listEl.querySelectorAll('.st-fr-dest').forEach(function(btn){
            btn.addEventListener('click', function(){
                var name = this.getAttribute('data-name');
                var addr = this.getAttribute('data-address');
                var basePrice = parseFloat(this.getAttribute('data-price'));
                var paxNow = parseInt(document.getElementById('st-fr-pax').value||1);
                var finalPrice = paxNow >= 3 ? basePrice * 1.40 : basePrice;

                /* Set dropoff field */
                var dropoffEl = document.getElementById('st-dropoff');
                if(dropoffEl){ dropoffEl.value = addr || name; }

                /* Set passengers on main form */
                var mainPax = document.getElementById('st-passengers');
                if(mainPax){ mainPax.value = paxNow; }

                /* Apply fare using pre-calculated finalPrice directly */
                var fr = {name:name, address:addr, price:finalPrice};
                /* Set passenger count on main form */
                var mainPaxEl = document.getElementById('st-passengers');
                if(mainPaxEl) mainPaxEl.value = paxNow;
                /* Apply with price already set to the pax-adjusted amount */
                stActiveFlatRate = {name:name, address:addr, price:basePrice};
                stCalcFare = finalPrice; stFinalFare = finalPrice; stDiscountAmt = 0;
                var milesEl=document.getElementById('st-fare-miles');
                var amountEl=document.getElementById('st-fare-amount');
                var fareBox=document.getElementById('st-fare-box');
                if(milesEl) milesEl.textContent='Flat Rate';
                if(amountEl) amountEl.textContent='$'+finalPrice.toFixed(2);
                if(fareBox){
                    fareBox.style.display='block';
                    var badge=fareBox.querySelector('.st-flat-rate-badge');
                    if(!badge){badge=document.createElement('div');badge.className='st-flat-rate-badge';badge.style.cssText='font-size:.75rem;color:#f5c518;margin-top:4px;font-style:italic;';fareBox.appendChild(badge);}
                    badge.textContent = paxNow>=3
                        ? paxNow+' passengers: base $'+basePrice.toFixed(2)+' + 40% = $'+finalPrice.toFixed(2)
                        : 'Fixed rate: '+name;
                }
                stSyncTotals();
                var exactWrap=document.getElementById('st-exact-address-wrap');
                if(exactWrap){exactWrap.style.display='block';var ei=document.getElementById('st-dropoff-exact');if(ei){ei.value='';setTimeout(function(){ei.focus();},100);}}

                /* Clear exact address for fresh entry */
                var exactEl=document.getElementById('st-dropoff-exact');
                if(exactEl) exactEl.value='';

                /* Close popup */
                stCloseFlatRatePopup();
            });
        });
    }

    /* Passenger change */
    document.getElementById('st-fr-pax').addEventListener('change', function(){
        var pax = parseInt(this.value||1);
        var note = document.getElementById('st-fr-pax-note');
        if(note) note.style.display = pax >= 3 ? 'inline' : 'none';
        var activeTab = modal.querySelector('.st-fr-tab[data-active="1"]');
        var block = activeTab ? activeTab.getAttribute('data-block') : 'north_bound';
        renderBlock(block);
    });

    /* Tab switching */
    modal.querySelectorAll('.st-fr-tab:not([disabled])').forEach(function(tab){
        tab.addEventListener('click', function(){
            modal.querySelectorAll('.st-fr-tab').forEach(function(t){
                t.style.background='rgba(255,255,255,.04)';
                t.style.borderColor='rgba(255,255,255,.15)';
                t.style.color='rgba(255,255,255,.5)';
                t.removeAttribute('data-active');
            });
            this.style.background='rgba(200,168,75,.15)';
            this.style.borderColor='#c8a84b';
            this.style.color='#c8a84b';
            this.setAttribute('data-active','1');
            renderBlock(this.getAttribute('data-block'));
        });
    });

    /* Set north_bound as default active tab */
    var defaultTab = modal.querySelector('.st-fr-tab[data-block="north_bound"]');
    if(defaultTab){
        defaultTab.style.background='rgba(200,168,75,.15)';
        defaultTab.style.borderColor='#c8a84b';
        defaultTab.style.color='#c8a84b';
        defaultTab.setAttribute('data-active','1');
    }
    renderBlock('north_bound');

    /* Close handlers */
    document.getElementById('st-fr-close').addEventListener('click', stCloseFlatRatePopup);
    overlay.addEventListener('click', function(e){ if(e.target===overlay) stCloseFlatRatePopup(); });
}

function stCloseFlatRatePopup(){
    stFlatRatePopupOpen = false;
    var el = document.getElementById('st-fr-overlay');
    if(el) el.remove();
}

document.addEventListener('DOMContentLoaded',function(){
    var calBtn=document.getElementById('st-show-calendar'),calWrap=document.getElementById('st-cal-wrap');
    if(calBtn&&calWrap){calBtn.addEventListener('click',function(e){e.preventDefault();calWrap.style.display=calWrap.style.display==='none'?'block':'none';calBtn.textContent=calWrap.style.display==='none'?'View open times below':'Hide calendar';});}

    /* Re-apply flat rate when passenger count changes */
    var paxSel=document.getElementById('st-passengers');
    if(paxSel){paxSel.addEventListener('change',function(){
        if(stActiveFlatRate) stApplyFlatRate(stActiveFlatRate);
    });}

    /* Global PAC click interceptor - close dropdown after any pac-item click */
    document.addEventListener('click', function(e){
        var pac = document.querySelector('.pac-container');
        if(pac && !pac.contains(e.target)){
            stClosePac();
        }
    }, true);

    /* Also hide PAC on scroll */
    window.addEventListener('scroll', stClosePac, {passive:true});

    /* Flat rate link */
    var frLink=document.getElementById('st-flatrate-link');
    if(frLink){frLink.addEventListener('click',function(e){e.preventDefault();stOpenFlatRatePopup();});}

    /* Show/hide flat rate hint based on pickup */
    var pickupEl=document.getElementById('st-pickup');
    if(pickupEl){
        pickupEl.addEventListener('input', stShowFlatRateHint);
        pickupEl.addEventListener('change', stShowFlatRateHint);
    }

    /* Exact address locate button */
    var locExactBtn=document.getElementById('st-locate-exact');
    if(locExactBtn){locExactBtn.addEventListener('click',function(){
        if(!navigator.geolocation){alert('Geolocation not supported.');return;}
        locExactBtn.textContent='?';
        navigator.geolocation.getCurrentPosition(function(pos){
            locExactBtn.textContent='?';
            var ll=new google.maps.LatLng(pos.coords.latitude,pos.coords.longitude);
            stReverseGeocodeExact(ll);
            if(stMap) stMap.panTo(ll);
        },function(){locExactBtn.textContent='?';alert('Could not get location.');});
    });}

    var locBtn=document.getElementById('st-locate-me');
    if(locBtn){locBtn.addEventListener('click',function(){if(!navigator.geolocation){alert('Geolocation not supported.');return;}locBtn.textContent='\u231b';navigator.geolocation.getCurrentPosition(function(pos){locBtn.textContent='\ud83d\udccd';var ll=new google.maps.LatLng(pos.coords.latitude,pos.coords.longitude);stReverseGeocode(ll,'pickup');if(stMap) stMap.panTo(ll);},function(){locBtn.textContent='\ud83d\udccd';alert('Could not get location.');});});}

    var couponBtn=document.getElementById('st-apply-coupon');
    if(couponBtn){couponBtn.addEventListener('click',function(){var code=(document.getElementById('st-coupon')||{}).value||'';var msg=document.getElementById('st-coupon-msg');if(!code.trim()){if(msg) msg.textContent='Enter a coupon code.';return;}var fd=new FormData();fd.append('action','st_check_coupon');fd.append('nonce',ST.nonce);fd.append('code',code);fd.append('fare',stCalcFare);fetch(ST.ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(msg){msg.textContent=d.msg||'';msg.style.color=d.valid?'green':'red';}if(d.valid){stDiscountAmt=d.discount;stFinalFare=d.new_fare;stSyncTotals();}});});}

    /* Step indicator click - back navigation */
    for(var si=1;si<=3;si++){
        (function(stepNum){
            var ind=document.getElementById('step-ind-'+stepNum);
            if(ind){
                ind.style.cursor='pointer';
                ind.addEventListener('click',function(){
                    if(this.classList.contains('done')) showStep(stepNum);
                });
            }
        })(si);
    }

    var next1=document.getElementById('st-next-1');
    if(next1){next1.addEventListener('click',function(){
        var date=(document.getElementById('st-date')||{}).value||'',
            time=(document.getElementById('st-time')||{}).value||'',
            pickup=(document.getElementById('st-pickup')||{}).value||'',
            dropoff=(document.getElementById('st-dropoff')||{}).value||'',
            errEl=document.getElementById('st-form-error-1');
        if(!date||!time||!pickup||!dropoff){if(errEl){errEl.textContent='Please fill in date, time, pickup and dropoff.';errEl.style.display='block';}return;}
        if(!stCalcFare){
            if(errEl){errEl.textContent='Please wait while we calculate your route...';errEl.style.display='block';}
            var gc=new google.maps.Geocoder();
            gc.geocode({address:pickup+', Michigan, USA'},function(r1,s1){
                if(s1==='OK'&&r1[0]){
                    stPickupLatLng=r1[0].geometry.location;
                    stPlaceMarker('pickup',stPickupLatLng,pickup);
                    gc.geocode({address:dropoff+', Michigan, USA'},function(r2,s2){
                        if(s2==='OK'&&r2[0]){
                            stDropoffLatLng=r2[0].geometry.location;
                            stPlaceMarker('dropoff',stDropoffLatLng,dropoff);
                            stTryRoute();
                            setTimeout(function(){if(errEl) errEl.style.display='none';showStep(2);},1500);
                        }
                    });
                }
            });
            return;
        }
        if(errEl) errEl.style.display='none';
        showStep(2);
    });}

    var back2=document.getElementById('st-back-2');
    if(back2) back2.addEventListener('click',function(){showStep(1);});

    var confirmBtn=document.getElementById('st-confirm-btn');
    if(confirmBtn){confirmBtn.addEventListener('click',function(){
        var name=(document.getElementById('st-name')||{}).value||'',
            phone=(document.getElementById('st-phone')||{}).value||'',
            errEl=document.getElementById('st-form-error-2');
        if(!name.trim()||!phone.trim()){
            if(errEl){errEl.textContent='Please enter your name and phone number.';errEl.style.display='block';}
            return;
        }
        if(errEl) errEl.style.display='none';
        stSyncTotals();
        stShowPaymentPopup();
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

