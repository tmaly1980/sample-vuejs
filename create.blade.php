@extends('layouts.dashboard',['sidenav'=> 'false','bodyClass'=>'businesses'])

@section('meta-controller','businesses')
@section('meta-id',$business->id)

@section('javascript')
@parent
<!-- <script src="/js/common/business-map.js"></script> -->
<!-- <script src="/js/common/review-expander.js"></script> -->
<!-- <script src="https://maps.googleapis.com/maps/api/js?key={{ \Config::get('app.google_maps_key') }}&libraries=places&callback=initGoogleServices" async defer></script> -->
<!-- <script src="/js/services/cropper.js"></script> -->
<!-- <script src="/js/business-images.js"></script> -->
@stop

@section('content')

<div id='business-signup-container' v-cloak class="business-register container">

    <h2>
        Hi {{ Auth::User()->first_name }}, let's get started by setting up your business page
    </h2>
    <hr/>

    {!! BootForm::open(['ref'=>'form','id'=>'businessForm','model' => $business, 'store' => 'businesses.store', 'update' => 'businesses.update']); !!}
    <div class='row'>

        <div class='col-md-6'>
            <div class='pull-right'>
                <!-- TODO 'start over'? -->
            </div>
            <h4>Step 1: Claim Business</h6>
                {!! BootForm::text("name", "What's your business name?", null, ['v-model'=>"business.name","placeholder"=>"Business Name","v-on:keyup.enter.stop"=>"maybeSearchBusiness",'help_text'=>"Your business name will show on your page, deals, and searches"]) !!}

            <div class='row'>

                {!! BootForm::text("address_zip_code", "What's your zip code?", null, ['div'=>'col-md-6',"v-on:keyup.enter.stop"=>"maybeSearchBusiness",'v-model'=>"business.address_zipcode","placeholder"=>"Zip Code"]) !!}

                <div class='form-group text-right col-md-6'>
                    <br/>
                    <a href="javascript:void(0)" class="btn btn-default" v-if="readyToSearch" v-on:click.prevent="searchBusiness">Find My Business
                    <i :class=" 'fa '+ (searching && !searched ? 'fa-spinner fa-spin' : 'fa-chevron-right') "></i>
                    </a>
                </div>


            </div>

            <gmap v-show="false" ref="map" style="width: 100%; height: 400px;" :center="map_center" :zoom="mapzoom">
                <!-- todo markers , once results details ok -->
                <gmarker v-for="place in google_results" :position="place.geometry.location" :clickable="true" @click="map_center=place.geometry.location">
                </gmarker>
                <gmarker v-for="place in mytown_businesses" :position="mytownCoords(place)" :clickable="true" @click="map_center=mytownCoords(place)">
                </gmarker>
            </gmap>
        </div>

        <div class='col-md-6'>
            <div class='' v-if="searched">

                <div v-if="!mytown_city">
                    <h4>Sorry, you don't seem to be in any of our available networks</h4>
                    <!-- TODO request -->
                    yada yada
                </div>

                <div v-if="mytown_businesses.length || google_results.length">
                    <div class="alert alert-info">
                            <i class='fa fa-info-circle'></i>
                            We found the following business pages in @{{ mytown_city.name }}:
                    </div>

                    <div class='row minheight125' v-if="mytown_businesses.length" v-for="mytown_business in mytown_businesses">
                            <div class='col-md-3 padding5'>
                                <img :src="mytownPhoto(mytown_business)" class='maxwidth100p border'/>
                            </div>
                            <div class='col-md-6'>
                                <div v-if="mytown_business.owner_id">
                                    <span class='label label-success'>
                                        <i class='fa fa-check'></i>
                                        Claimed
                                    </span>
                                </div>
                                <div v-else>
                                    <span class='label label-danger'>
                                        <i class='fa fa-question'></i>
                                        Unclaimed
                                    </span>
                                </div>
                                <div class='font1p1em bold'>@{{ mytown_business.name }}</div>
                                <div v-html="mytown_business.address"></div>
                                <b>@{{ mytown_business.phone }}</b>
                            </div>
                            <div class='col-md-3'>
                                <br/>
                                <div v-if="mytown_business.owner_id">
                                    <a :href=" mytown_business.mytown_url " class="btn btn-sm btn-default block">View Page</a>
                                </div>
                                <div v-else>
                                    <a :href=" '/business/'+mytown_business.id+'/claim' " class="btn btn-sm btn-primary block">Claim</a>
                                </div>
                            </div>
                    </div>

                    <div class='row text-center'>
                        <a href="javascript:void(0)" class='btn' v-if="mytown_businesses.length && !searched_google && !searching_google" v-on:click="searchGoogle">
                            See more results
                            <i :class=" 'fa '+ (searching && !searched ? 'fa-spinner fa-spin' : 'fa-chevron-right') "></i>
                        </a>
                    </div>

                    <div v-if="google_results.length">
                        <div class='row minheight125' v-for="place in google_results">
                            <div class='col-md-3'>
                                <!-- TODO save photo url??? or later when added as part of place_id trigger -->
                                <img :src="googlePhoto(place)" class='maxwidth100p'/>
                            </div>
                            <div class='col-md-6'>
                                <div>
                                    <span class='label label-success'>
                                        <i class='fa fa-question'></i>
                                        Unclaimed
                                    </span>
                                </div>
                                <div class='font1p1em bold'>@{{ place.name }}</div>
                                <div v-html="prettyGoogleAddress(place)"></div>
                                <b>@{{ place.formatted_phone_number }}</b>
                            </div>
                            <div class='col-md-3'>
                                <a href="javascript:void(0)" class="btn btn-sm btn-primary block" v-on:click="setBusiness(place,false)">
                                    <span v-if="creatingGooglePlace != place.place_id">Claim</span>
                                    <span v-else>Creating <i class='fa fa-spin fa-spinner'></i></span>

                                </a>
                                <!-- should fill out hidden form, then submit to next page -->
                            </div>
                        </div>

                    </div>
                    <div v-else-if="searched_google && !google_results.length">
                        <div class="alert alert-info">
                            <i class='fa fa-info-circle'></i>
                            No more businesses found
                        </div>
                    </div>

                <hr/>

                </div>

                <div class="alert alert-info" v-if="noResultsAnywhere()">
                    <i class='fa fa-info-circle'></i>
                    We couldn't find your business
                </div>

                <a href="javascript:void(0)" class="btn" v-if="searched_google && !custom_business && !noResultsAnywhere()" v-on:click="addCustomBusiness">Add a different business</a>
                <!-- XXX TODO re-center map on address, once finished typing -->

                <div class='row business-details' v-show="custom_business || noResultsAnywhere()">
                    <h4>Add your business details:</h4><br>
                    <div class="alert alert-info"><i class='fa fa-info-circle'></i> Please select address from dropdown.</div>
                    <!-- still used, but hidden, for google claim -->

<!--                     <vinput type="text" class="col-md-12" XXXXname="address_street" XXXXv-model="business.address_street" placeholder="Street Address" required></vinput> -->
                    <div class='form-group col-md-12'>

                        <gautocomplete name="address_street" v-model="business.address_street" class="form-control auto-address" placeholder="Street Address" required @place_changed="setBusiness" :options="{location: myLocation, rankBy: rankBy}"></gautocomplete>

                    </div>

                    {!! BootForm::text("address_city", false, null, ['div'=>'col-md-6','v-model'=>"business.address_city", 'required'=>'required',"placeholder"=>"City","v-on:keyup.enter.stop"=>"doNothing"]) !!}

                    {!! BootForm::text("address_state", false, null, ['div'=>'col-md-6','v-model'=>"business.address_state", 'required'=>'required',"placeholder"=>"State","v-on:keyup.enter.stop"=>"doNothing"]) !!}

                    {!! BootForm::text("phone", false, null, ['div'=>'col-md-6','v-model'=>"business.phone", 'required'=>'required',"placeholder"=>"Phone Number","v-on:keyup.enter.stop"=>"doNothing"]) !!}

                    <input type="hidden"  name="longitude" v-model="business.longitude"></input>

                    <input type="hidden"  name="latitude" v-model="business.latitude"></input>

                    <input type="hidden"  name="place_id" v-model="business.place_id"></input>

                    <input type="hidden"  name="city_id" v-model="business.city_id"></input>

                    <input type="hidden"  name="profile_url" v-model="business.profile_url"></input>

                    <div class='text-right col-md-6'>
                        <a href="javascript:void(0)" class="btn btn-primary" v-on:click="createBusiness" v-if="formComplete">
                            Create Business Page
                            <i :class=" 'fa '+ (creating ? 'fa-spinner fa-spin' : 'fa-chevron-right') "></i>
                        </a>
                    </div>
                </div>



                <div class="col-md-4">

                </div>
                <div class="col-md-8">



                </div>
            </div>

            <div v-else>
                <img src="/img/default-profile.png" class='img-circle maxwidth100p'/>
            </div>

            <hr/>
        </div>

    </div>
    {!! Form::close() !!}
</div>
<script>
VueGoogleMaps.load({
    key: "{{ \Config::get('app.google_maps_key') }}",
    libraries: 'places'
});

document.addEventListener('DOMContentLoaded', function() {

    window.app = new Vue({
        el: "#business-signup-container",
        components: {
/*            vinput: VueStrap.input,
            vradio: VueStrap.radio,
            vcheckbox: VueStrap.checkbox,
            */
            gmap: VueGoogleMaps.Map,
            gmarker: VueGoogleMaps.Marker,
            gautocomplete: VueGoogleMaps.Autocomplete
        },
        data: {
            mapzoom: 12,
            rankBy: null,
            creating: false,
            creatingGooglePlace: null,
            default_center: {lat: 35.74540000, lng: -81.68480000},
            map_center: {lat: 35.74540000, lng: -81.68480000},
            searched_google: false,
            searching_google: false,
            mytown_city: null,
            mytown_zipcode: null,
            selected_place_id: null,
            default_profile: "/img/default-profile.png",
            preview_photo: "",
            searched: false, // set only once results back
            searching: false,
            google_results: [],
            custom_business: false,
            mytown_businesses: [],
            business: {
                name: null,
                address_street: null,
                address_city: null,
                address_state: null,
                address_zipcode: null,
                place_id: null,
                city_id: null,
                latitude: null,
                longitude: null,
                phone: null,
                profile_url: null
            },

        },
        places: null,
        google: null,
        computed: {
            formComplete: function() {
                return this.business.name && this.business.address_street && this.business.address_city &&
                    this.business.address_state && this.business.address_zipcode && this.business.phone;
            },
            myLocation: function() {
                return this.mytown_zipcode ?
                    new window.google.maps.LatLng(this.mytown_zipcode.latitude, this.mytown_zipcode.longitude) :
                    (this.mytown_city ?
                        new window.google.maps.LatLng(this.mytown_city.latitude, this.mytown_city.longitude) : null);
            },
            readyToSearch: function() {
                return this.business.name && this.business.address_zipcode && this.business.address_zipcode.length >= 5;
            }
        },
        methods: {
            doNothing: function() {
                console.log("STOP!");
            },
            mytownPhoto: function(business)
            {
                return business.profile_photo_url ? business.profile_photo_url : this.default_profile;
            },
            noResultsAnywhere: function() {
                return (this.searched && !this.mytown_businesses.length &&
                    this.searched_google && !this.google_results.length);
            },
            googlePhoto: function(place,showDefault=true) {
                console.log("GOOGLE PLACE PHOTO=");
                console.log(place);
                console.log(place.photos);
                if(place && place.photos && place.photos.length)
                {
                    url = place.photos[0].getUrl({ maxWidth: 400, maxHeight: 400});
                    console.log("PHOTO URL="+url);
                    return url;
                } else if (showDefault) {
                    return this.default_profile;
                } else {
                    return null;
                }
            },
            addCustomBusiness: function() {
                // Default city, state
                this.business.address_city = this.mytown_city.name;
                this.business.address_state = this.mytown_city.stateAbbr;

                this.custom_business = true;

                setTimeout(function() {
                    $('input[name=address_street]').get(0).focus();
                }, 500);
            },
            maybeSearchBusiness: function() {
                console.log("MAYBE SEARCH????");
                // return false;
                if(this.readyToSearch)
                {
                    this.searchBusiness();
                }
            },
            searchBusiness: function() {
                this.custom_business = false; // hide form
                this.searched = false;
                this.creatingGooglePlace = null;
                this.searching = true;
                this.searched_google = false;
                this.google_results = []; // clear

                var data = {
                    name: this.business.name,
                    zip_code: this.business.address_zipcode
                };

                this.$http.post("/businesses/findMyBusiness", data).then(function(response)
                {
                    console.log(response);
                    this.mytown_city = response.body.city;
                    this.mytown_zipcode = response.body.zipcode;
                    this.business.city_id = this.mytown_city ? this.mytown_city.id : null;

                    if(this.mytown_city)
                    {
                        this.map_center = this.mytownCoords(this.mytown_city);
                        this.refreshMap();
                    }

                    this.mytown_businesses = response.body.businesses;
                    if(this.mytown_city && this.mytown_zipcode && !this.mytown_businesses.length)
                    {
                        this.searchGoogle(); // fall-back if not in mytown
                    } else {
                        this.searched = true;
                    }
                });
            },
            mytownCoords: function(place) { // business or city
                if(!place)
                {
                    place = this.mytown_city; // fail-safe
                }
                if(!place) // Not in any network
                {
                    return null;
                }
                return {
                    lat: parseFloat(place.latitude),
                    lng: parseFloat(place.longitude)
                };
            },
            setGoogleDetails: function(place,status) {
              if (status == window.google.maps.places.PlacesServiceStatus.OK) {
                console.log(place);
                app.google_results.push(place);
              }
            },
            searchGoogle: function() {
                // DEPENDS on city latitude/longitude
                console.log("SEARCHING google...");
                this.searching_google = true;

                // var request = {
                //     query: this.business.name+", "+this.business.address_zipcode
                // };
                // this.places.textSearch(request, function(results,status) {
                //     app.searching = false;
                //     app.searched = true;
                //     app.setGoogleResults(results);
                // });

                // MAYBE better to convert zip code given to coords

                var request = {
                    location: this.myLocation,
                    rankBy: this.rankBy,
                    // rankBy: google.maps.places.RankBy.PROMINENCE,
                    name: this.business.name,
                    // radius: 50000 // ~30 miles
                };
                this.places.nearbySearch(request, function(results,status) {
                    console.log(results);
                    app.searching = false;
                    app.searched = true;
                    app.google_results = [];
                    for(var r = 0; r < results.length; r++)
                    {
                        var result = results[r];

                        // Need to do subquery to get accurate details....
                        app.places.getDetails({placeId: result.place_id},
                            function(place,status) {
                                app.setGoogleDetails(place,status)
                        });
                    }
                    console.log(app.google_results);
                    app.searched_google = true;
                    app.searching_google = false;
                });
            },

            setBusiness: function(place,autocompleted = true) {
                console.log(place);
                // this.custom_business = true; // FOR NOW, REMOVE LATER

                if(!place)
                {
                    this.creatingGooglePlace = null;
                    this.business.place_id = null;
                    this.business.latitude = null;
                    this.business.longitude = null;
                    this.business.address_street = null;
                    this.business.address_city = null;
                    this.business.address_state = null;

                } else {
                    this.creatingGooglePlace = place.place_id;
                    if(place.formatted_phone_number) {
                        this.business.phone = place.formatted_phone_number;
                    }
                    this.business.place_id = place.place_id;

                    // split formatted_address into parts
                    var address = this.googleAddressParts(place);

                    this.business.address_street = address.street;
                    this.business.address_city = address.city;
                    this.business.address_state = address.state;

                    this.business.latitude = place.geometry.location.lat();
                    this.business.longitude = place.geometry.location.lng();

                    this.business.profile_url = this.googlePhoto(place,false);

                    if(!autocompleted) // Don't change name when they do autocomplete
                    {
                        this.business.name = place.name;
                    }


                    if(address.zip_code)
                    {
                        this.business.address_zipcode = address.zip_code;
                    }

                    // Submit form
                    this.createBusiness();
                }
            },
            googleAddressParts: function(place) {
                var address_parts = place.formatted_address.split(", ");
                var state_zip = address_parts[2].match(/(.*)\s+([0-9-]+)/);

                return {
                    street: address_parts[0],
                    city: address_parts[1],
                    state: state_zip.length > 1 ? state_zip[1] : address_parts[2],
                    zip_code: state_zip.length > 2 ? state_zip[2] : null
                };
            },
            prettyGoogleAddress: function(place) {
                return place.formatted_address.split(", ").slice(0,3).join("<br/>");
            },
            initPlaces: function() {
                app.rankBy = window.google.maps.places.RankBy.DISTANCE;

                this.$refs.map.$mapCreated.then(function() {
                    console.log("places init");
                    app.places = new window.google.maps.places.PlacesService(app.$refs.map.$mapObject);
                    console.log("DONE PLACES");
                    // app.rankByDistance = app.places.RankBy.DISTANCE; // save for convenience
                });
            },
            refreshMap: function() {
                setTimeout(function() {
                    app.$refs.map.resizePreserveCenter(); // refresh
                }, 500); // some delay before re-render called
                // XXX TODO
            },
            createBusiness: function() {
                // localStorage.setItem('businessCreate', app.$data);

                // Switch these lines to hide/show address input for google search results
                // this.custom_business = true;

                if(this.formComplete)
                {
                    console.log("CREATING BUSINESS.......");

                    this.creating = true;
                    setTimeout(function() {
                        app.$refs.form.submit();
                    }, 500); // give vuejs some time to fill in fields.

                }

            }
        },
        mounted: function() {
            // app.$data = localStorage.getItem('businessCreate');
        },
        watch: {
            // '$route': function(to, from) {
            //     // Call resizePreserveCenter() on all maps
            //     Vue.$gmapDefaultResizeBus.$emit('resize')
            // }
        }
    });

    VueGoogleMaps.loaded.then(function() {
        console.log(app);
        app.initPlaces();
    });

    Vue.directive('focus', {
        // When the bound element is inserted into the DOM...
        inserted: function (el) {
          // Focus the element
          el.focus()
        }
      });
});

$('#businessForm').submit(function(e) {
    console.log("NOOOOOOOOOOOOOOOOO");
    alert('stop!');
    e.preventDefault();
    return false;
});


</script>

@stop
