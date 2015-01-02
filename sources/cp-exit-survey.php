<!-- BEGIN TEMPLATE: cp_exit_survey -->
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head><script type="text/javascript">window.NREUM||(NREUM={}),__nr_require=function(t,e,n){function r(n){if(!e[n]){var o=e[n]={exports:{}};t[n][0].call(o.exports,function(e){var o=t[n][1][e];return r(o?o:e)},o,o.exports)}return e[n].exports}if("function"==typeof __nr_require)return __nr_require;for(var o=0;o<n.length;o++)r(n[o]);return r}({QJf3ax:[function(t,e){function n(t){function e(e,n,a){t&&t(e,n,a),a||(a={});for(var c=u(e),f=c.length,s=i(a,o,r),p=0;f>p;p++)c[p].apply(s,n);return s}function a(t,e){f[t]=u(t).concat(e)}function u(t){return f[t]||[]}function c(){return n(e)}var f={};return{on:a,emit:e,create:c,listeners:u,_events:f}}function r(){return{}}var o="nr@context",i=t("gos");e.exports=n()},{gos:"7eSDFh"}],ee:[function(t,e){e.exports=t("QJf3ax")},{}],gos:[function(t,e){e.exports=t("7eSDFh")},{}],"7eSDFh":[function(t,e){function n(t,e,n){if(r.call(t,e))return t[e];var o=n();if(Object.defineProperty&&Object.keys)try{return Object.defineProperty(t,e,{value:o,writable:!0,enumerable:!1}),o}catch(i){}return t[e]=o,o}var r=Object.prototype.hasOwnProperty;e.exports=n},{}],D5DuLP:[function(t,e){function n(t,e,n){return r.listeners(t).length?r.emit(t,e,n):(o[t]||(o[t]=[]),void o[t].push(e))}var r=t("ee").create(),o={};e.exports=n,n.ee=r,r.q=o},{ee:"QJf3ax"}],handle:[function(t,e){e.exports=t("D5DuLP")},{}],XL7HBI:[function(t,e){function n(t){var e=typeof t;return!t||"object"!==e&&"function"!==e?-1:t===window?0:i(t,o,function(){return r++})}var r=1,o="nr@id",i=t("gos");e.exports=n},{gos:"7eSDFh"}],id:[function(t,e){e.exports=t("XL7HBI")},{}],loader:[function(t,e){e.exports=t("G9z0Bl")},{}],G9z0Bl:[function(t,e){function n(){var t=l.info=NREUM.info;if(t&&t.agent&&t.licenseKey&&t.applicationID&&c&&c.body){l.proto="https"===p.split(":")[0]||t.sslForHttp?"https://":"http://",a("mark",["onload",i()]);var e=c.createElement("script");e.src=l.proto+t.agent,c.body.appendChild(e)}}function r(){"complete"===c.readyState&&o()}function o(){a("mark",["domContent",i()])}function i(){return(new Date).getTime()}var a=t("handle"),u=window,c=u.document,f="addEventListener",s="attachEvent",p=(""+location).split("?")[0],l=e.exports={offset:i(),origin:p,features:{}};c[f]?(c[f]("DOMContentLoaded",o,!1),u[f]("load",n,!1)):(c[s]("onreadystatechange",r),u[s]("onload",n)),a("mark",["firstbyte",i()])},{handle:"D5DuLP"}]},{},["G9z0Bl"]);</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Precision Nutrition - Coaching Progress Questionnaire</title>
    <link rel="stylesheet" type="text/css" href="css/cp-survey.css" />
</head>
<body>
<div class="main_header">
    <div class="wrapper_header_fluid">
        <div class="logo">
            <img src="/wordpress/wp-content/themes/pn2012/images/pn_logo_horizontal.svg" scale="0">
        </div>
        <div class="navigation">
            <a href="/coaching/home">Coaching Home</a>
        </div>
    </div>
</div>
<div class="wrapper">
    <!-- BEGIN TEMPLATE: cp_exit_survey_edit -->
    <h1>PN Coaching Progress Questionnaire</h1>



    <div class="section">
        <img class="jb_pic" src="/images/meet-the-team/John.jpg" align="right" />
        <p>With the year of coaching wrapping up, I'd like to take this chance to learn a bit more about your experience.</p>
        <p>Please take a few minutes to answer all the questions as completely as possible. Your feedback is extremely important to us and will help us improve and shape future coaching programs.</p>
        <p>Much appreciated,</p>
        <img src="/images/sig.gif" />
    </div>

    <form action="cp-exit-survey.php" accept-charset="UTF-8 ISO-8859-1" id="surveyForm" method="POST" >
        <input type="hidden" name="u" value="12798" />
        <input type="hidden" name="completed" value="1" />
        <input type="hidden" name="action" value="submit" />

        <div class="section">
            <h2>Part 1: Movement and exercise</h2>

            <div class="intake-question">
                <div class="input-text">How much do you now weigh? <span class="required">*</span></div>
                <div class="div_table">
                    <div class="div_row">
                        <div class="div_cell"><div class="weight-errormessage"></div></div>
                    </div>
                    <div class="div_row">
                        <div class="div_cell">
                            <div class="input-field"><input name="weight" type="text" /></div>
                        </div>
                        <div class="div_cell">
                            <div class="input-field select">
                                <select id="weightunits" name="weightunits" value="lbs">
                                    <option value="lb" selected>Pounds</option>
                                    <option value="kg" >Kilograms</option>
                                    <option value="st" >Stone</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">On average, how many times per week did you do the assigned workout? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="exercise_consistency_none" type="radio" name="exercise-consistency" value="none" /> <label for="exercise_consistency_none">0 (I didn't workout)</label><br />
                    <input id="exercise_consistency_other" type="radio" name="exercise-consistency" value="other" /> <label for="exercise_consistency_other">0 (I did my own workouts)</label><br />
                    <input id="exercise_consistency_12" type="radio" name="exercise-consistency" value="1-2" /> <label for="exercise_consistency_12">1-2</label><br />
                    <input id="exercise_consistency_34" type="radio" name="exercise-consistency" value="3-4" /> <label for="exercise_consistency_34">3-4</label><br />
                    <input id="exercise_consistency_57" type="radio" name="exercise-consistency" value="5-7" /> <label for="exercise_consistency_57">5-7</label><br />
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Throughout the program, how much did you exercise during an average week? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="exercise_frequency_0" type="radio" name="exercise-frequency" value="0" /> <label for="exercise_frequency_0">0 hours</label><br />
                    <input id="exercise_frequency_13" type="radio" name="exercise-frequency" value="1-3" /> <label for="exercise_frequency_13">1-3 hours</label><br />
                    <input id="exercise_frequency_35" type="radio" name="exercise-frequency" value="3-5" /> <label for="exercise_frequency_35">3-5 hours</label><br />
                    <input id="exercise_frequency_57" type="radio" name="exercise-frequency" value="5-7" /> <label for="exercise_frequency_57">5-7 hours</label><br />
                    <input id="exercise_frequency_7plus" type="radio" name="exercise-frequency" value="7+" /> <label for="exercise_frequency_7plus">7+ hours</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">What types of exercise did you do regularly during the past 12 months (at least once a week)? <span class="required">*</span></div>
                <div class="input-note">Check all that apply:</div>
                <div class="input-field checkbox">
                    <input id="exercise_types_cardio" type="checkbox" name="exercise-types[]" value="cardio" /> <label for="exercise_types_cardio">Light cardio exercise (e.g. walking, golf, casual bike riding, Aquafit, etc.)</label><br />
                    <input id="exercise_types_endurance" type="checkbox" name="exercise-types[]" value="endurance" /> <label for="exercise_types_endurance">Higher intensity endurance exercise (e.g. running, cycling, swimming, rowing, etc.)</label><br />
                    <input id="exercise_types_strength" type="checkbox" name="exercise-types[]" value="strength" /> <label for="exercise_types_strength">Strength exercise (e.g. weight lifting, gymnastics, body weight exercise, etc.)</label><br />
                    <input id="exercise_types_metabolic" type="checkbox" name="exercise-types[]" value="metabolic" /> <label for="exercise_types_metabolic">Metabolic exercise (e.g. circuit training, interval training, CrossFit, etc.)</label><br />
                    <input id="exercise_types_flexibility" type="checkbox" name="exercise-types[]" value="flexibility" /> <label for="exercise_types_flexibility">Flexibility/posture or body awareness-oriented exercise (e.g. yoga, Pilates, tai chi, etc.)</label><br />
                    <input id="exercise_types_organized" type="checkbox" name="exercise-types[]" value="organized" /> <label for="exercise_types_organized">Organized and/or team sports (e.g. softball, hockey, tennis, etc.)</label><br />
                    <input id="exercise_types_other" type="checkbox" name="exercise-types[]" value="other" class="checkbox-unhide" data-unhide-id="exercise-types-other" /> <label for="exercise_types_other">Other</label>
                </div>
            </div>

            <div id="exercise-types-other" class="row-fluid question-hidden">
                <div class="intake-question">
                    <div class="input-text">Great! So what other activities?</div>
                    <div class="input-field"><textarea name="exercise-types-other"></textarea></div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">How would you describe your exercise level right now? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="exercise_level_beginner" type="radio" name="exercise-level" value="complete-beginner" /> <label for="exercise_level_beginner">Level 1: I'm still a complete beginner</label><br />
                    <input id="exercise_level_some" type="radio" name="exercise-level" value="some-experience" /> <label for="exercise_level_some">Level 2: I now have some experience but it's very basic</label><br />
                    <input id="exercise_level_comfortable" type="radio" name="exercise-level" value="comfortable" /> <label for="exercise_level_comfortable">Level 3: I feel comfortable with many different exercises and movement styles</label><br />
                    <input id="exercise_level_experienced" type="radio" name="exercise-level" value="very-experienced" /> <label for="exercise_level_experienced">Level 4: I'm now very experienced with different exercises and movement styles</label><br />
                    <input id="exercise_level_advanced" type="radio" name="exercise-level" value="advanced" /> <label for="exercise_level_advanced">Level 5: I'm now advanced and have had high level exercise and movement coaching</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Are you now suffering from chronic pain? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="chronic_pain_yes" type="radio" name="chronic-pain-yesno" value="yes" class="radio-unhide" data-unhide-id="chronic-pain-details" /> <label for="chronic_pain_yes">Yes</label><br />
                    <input id="chronic_pain_no" type="radio" name="chronic-pain-yesno" value="no" class="radio-hide" data-hide-id="chronic-pain-details" /> <label for="chronic_pain_no">No</label>
                </div>
            </div>

            <div id="chronic-pain-details" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Please describe your symptoms: <span class="required">*</span></div>
                    <div class="input-field"><textarea name="chronic-pain-details"></textarea></div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Do you currently have any injuries or movement limitations that make it difficult for you to exercise? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="injuries_yes" type="radio" name="injuries-yesno" value="yes" class="radio-unhide" data-unhide-id="injuries-yes" /> <label for="injuries_yes">Yes</label><br />
                    <input id="injuries_no" type="radio" name="injuries-yesno" value="no" class="radio-hide" data-hide-id="injuries-yes" /> <label for="injuries_no">No</label>
                </div>
            </div>

            <div id="injuries-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Select all injuries or movement limitations that make it difficult for you to exercise: <span class="required">*</span></div>
                    <div class="input-field checkbox" name="">
                        <input id="injuries_list_head" type="checkbox" name="injuries-list[]" value="head" /> <label for="injuries_list_head">Head</label><br />
                        <input id="injuries_list_neck" type="checkbox" name="injuries-list[]" value="neck" /> <label for="injuries_list_neck">Neck</label><br />
                        <input id="injuries_list_shoulder" type="checkbox" name="injuries-list[]" value="shoulder" /> <label for="injuries_list_shoulder">Shoulder</label><br />
                        <input id="injuries_list_elbow" type="checkbox" name="injuries-list[]" value="elbow" /> <label for="injuries_list_elbow">Elbow</label><br />
                        <input id="injuries_list_hand_wrist" type="checkbox" name="injuries-list[]" value="hand-wrist" /> <label for="injuries_list_hand_wrist">Hand / Wrist</label><br />
                        <input id="injuries_list_back" type="checkbox" name="injuries-list[]" value="back" /> <label for="injuries_list_back">Back</label><br />
                        <input id="injuries_list_hip" type="checkbox" name="injuries-list[]" value="hip" /> <label for="injuries_list_hip">Hip</label><br />
                        <input id="injuries_list_knee" type="checkbox" name="injuries-list[]" value="knee" /> <label for="injuries_list_knee">Knee</label><br />
                        <input id="injuries_list_ankle" type="checkbox" name="injuries-list[]" value="ankle" /> <label for="injuries_list_ankle">Ankle</label><br />
                        <input id="injuries_list_foot" type="checkbox" name="injuries-list[]" value="foot" /> <label for="injuries_list_foot">Foot</label><br />
                        <input id="injuries_list_other" type="checkbox" name="injuries-list[]" value="other" class="checkbox-unhide" data-unhide-id="injuries-other" /> <label for="injuries_list_other">Other</label>
                        <div id="injuries-other" class="other-hidden">
                            <div class="input-note">Please list:</div>
                            <div class="input-field"><input type="text" name="injuries-other"></div>
                        </div>
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">Please describe your injuries (what they are exactly, what happened, etc.). <span class="required">*</span></div>
                    <div class="input-field"><textarea name="injuries-details"></textarea></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 2: Your routines -- during and after coaching</h2>

            <div class="intake-question">
                <div class="input-text">Currently, who buys the groceries for your household? <span class="required">*</span></div>
                <div class="input-note">Check all that apply:</div>
                <div class="input-field checkbox">
                    <input id="groceries_self" type="checkbox" name="groceries[]" value="self" /> <label for="groceries_self">I do</label><br />
                    <input id="groceries_other" type="checkbox" name="groceries[]" value="other" class="checkbox-unhide" data-unhide-id="groceries-other" /> <label for="groceries_other">Someone else does</label>
                    <div id="groceries-other" class="other-hidden">
                        <div class="input-note">Who?</div>
                        <div class="input-field"><input type="text" name="groceries-other"></div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, who does the cooking for your household? <span class="required">*</span></div>
                <div class="input-note">Check all that apply:</div>
                <div class="input-field checkbox">
                    <input id="cooking_self" type="checkbox" name="cooking[]" value="self" /> <label for="cooking_self">I do</label><br />
                    <input id="cooking_other" type="checkbox" name="cooking[]" value="other" class="checkbox-unhide" data-unhide-id="cooking-other" /> <label for="cooking_other">Someone else does</label>
                    <div id="cooking-other" class="other-hidden">
                        <div class="input-note">Who?</div>
                        <div class="input-field"><input type="text" name="cooking-other"></div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, how many others in your household regularly eat meals with you? <span class="required">*</span></div>
                <div class="input-note">Check all that apply:</div>
                <div class="input-field checkbox">
                    <input id="shares_meals_self" type="checkbox" name="shares-meals[]" value="self" /> <label for="shares_meals_self">Just me</label><br />
                    <input id="shares_meals_partner" type="checkbox" name="shares-meals[]" value="partner" /> <label for="shares_meals_partner">A partner / roommate</label><br />
                    <input id="shares_meals_kids" type="checkbox" name="shares-meals[]" value="kids" /> <label for="shares_meals_kids">Kid(s)</label><br />
                    <input id="shares_meals_adults" type="checkbox" name="shares-meals[]" value="adults" /> <label for="shares_meals_adults">Parents / other adults</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, how many of your meals are prepared at home each day? <span class="required">*</span></div>
                <div class="input-field select">
                    <select id="home-meals" name="home-meals">
                        <option value="0">0 meals</option>
                        <option value="1-2">1-2 meals</option>
                        <option value="3-4">3-4 meals</option>
                        <option value="all">All my meals</option>
                    </select>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, how many meals do you eat in cafeterias or restaurants each day? <span class="required">*</span></div>
                <div class="input-field select">
                    <select id="eat-out-meals" name="eat-out-meals">
                        <option value="0">0 meals</option>
                        <option value="1-2">1-2 meals</option>
                        <option value="3-4">3-4 meals</option>
                        <option value="all">All my meals</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 3: Your food habits, preferences & sensitivities</h2>

            <div class="intake-question">
                <div class="input-text">Currently, are you following any of these diets? <span class="required">*</span></div>
                <div class="input-note">Check all that apply:</div>
                <div class="input-field checkbox">
                    <input id="special_diet_low_carb" type="checkbox" name="special-diet[]" value="low-carb" /> <label for="special_diet_low_carb">Low carbohydrate, high fat diet</label><br />
                    <input id="special_diet_high_carb" type="checkbox" name="special-diet[]" value="high-carb" /> <label for="special_diet_high_carb">High carbohydrate, low fat diet</label><br />
                    <input id="special_diet_high_protein" type="checkbox" name="special-diet[]" value="high-protein" /> <label for="special_diet_high_protein">High protein diet</label><br />
                    <input id="special_diet_paleo" type="checkbox" name="special-diet[]" value="paleo" /> <label for="special_diet_paleo">Paleo</label><br />
                    <input id="special_diet_vegan" type="checkbox" name="special-diet[]" value="vegan" /> <label for="special_diet_vegan">Vegetarian / vegan diet</label><br />
                    <input id="special_diet_blood_type" type="checkbox" name="special-diet[]" value="blood-type" /> <label for="special_diet_blood_type">Blood type diet</label><br />
                    <input id="special_diet_metabolic_typing" type="checkbox" name="special-diet[]" value="metabolic-typing" /> <label for="special_diet_metabolic_typing">Metabolic typing diet</label><br />
                    <input id="special_diet_pn" type="checkbox" name="special-diet[]" value="pn" /> <label for="special_diet_pn">No, I'm only following the habits outlined by PN</label><br />
                    <input id="special_diet_other" type="checkbox" name="special-diet[]" value="other" class="checkbox-unhide" data-unhide-id="special-diet-other" /> <label for="special_diet_other">Other</label>
                    <div id="special-diet-other" class="other-hidden">
                        <div class="input-note">Please specify:</div>
                        <div class="input-field"><input type="text" name="special-diet-other"></div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Do you currently have any food allergies? <span class="required">*</span></div>
                <div class="input-note">Food allergies cause more than mild discomfort, they cause life-threatening anaphylaxis.</div>
                <div class="input-field radio">
                    <input id="food_allergies_yes" type="radio" name="food-allergies-yesno" value="yes" class="radio-unhide" data-unhide-id="food-allergies-yes" /> <label for="food_allergies_yes">Yes</label><br />
                    <input id="food_allergies_no" type="radio" name="food-allergies-yesno" value="no" class="radio-hide" data-hide-id="food-allergies-yes" /> <label for="food_allergies_no">No</label>
                </div>
            </div>

            <div id="food-allergies-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Which foods are you allergic to? <span class="required">*</span></div>
                    <div class="input-note">Check all that apply:</div>
                    <div class="input-field checkbox">
                        <input id="food_allergies_dairy" type="checkbox" name="food-allergies[]" value="dairy" /> <label for="food_allergies_dairy">Dairy</label><br />
                        <input id="food_allergies_eggs" type="checkbox" name="food-allergies[]" value="eggs" /> <label for="food_allergies_eggs">Eggs</label><br />
                        <input id="food_allergies_gluten" type="checkbox" name="food-allergies[]" value="gluten" /> <label for="food_allergies_gluten">Wheat / gluten</label><br />
                        <input id="food_allergies_peanuts" type="checkbox" name="food-allergies[]" value="peanuts" /> <label for="food_allergies_peanuts">Peanuts</label><br />
                        <input id="food_allergies_tree_nuts" type="checkbox" name="food-allergies[]" value="tree-nuts" /> <label for="food_allergies_tree_nuts">Tree nuts</label><br />
                        <input id="food_allergies_shellfish" type="checkbox" name="food-allergies[]" value="shellfish" /> <label for="food_allergies_shellfish">Shellfish</label><br />
                        <input id="food_allergies_soy" type="checkbox" name="food-allergies[]" value="soy" /> <label for="food_allergies_soy">Soy</label><br />
                        <input id="food_allergies_other" type="checkbox" name="food-allergies[]" value="other" class="checkbox-unhide" data-unhide-id="food-allergies-other" /> <label for="food_allergies_other">Other</label>
                        <div id="food-allergies-other" class="other-hidden">
                            <div class="input-note">Please specify:</div>
                            <div class="input-field"><input type="text" name="food-allergies-other"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Are there any foods that you're currently intolerant or sensitive to? <span class="required">*</span></div>
                <div class="input-note">Intolerances cause milder symptoms than allergies, such as excessive gas, bloating, other GI upset, stuffiness, headaches, rashes, acne, etc.</div>
                <div class="input-field radio">
                    <input id="food_sensitivities_yes" type="radio" name="food-sensitivities-yesno" value="yes" class="radio-unhide" data-unhide-id="food-sensitivities-yes" /> <label for="food_sensitivities_yes">Yes</label><br />
                    <input id="food_sensitivities_no" type="radio" name="food-sensitivities-yesno" value="no" class="radio-hide" data-hide-id="food-sensitivities-yes" /> <label for="food_sensitivities_no">No</label>
                </div>
            </div>

            <div id="food-sensitivities-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Which foods are you intolerant to? <span class="required">*</span></div>
                    <div class="input-note">Check all that apply:</div>
                    <div class="input-field checkbox">
                        <input id="food_sensitivities_lactose" type="checkbox" name="food-sensitivities[]" value="lactose" /> <label for="food_sensitivities_lactose">Lactose intolerance</label><br />
                        <input id="food_sensitivities_milk" type="checkbox" name="food-sensitivities[]" value="milk" /> <label for="food_sensitivities_milk">Milk intolerance</label><br />
                        <input id="food_sensitivities_additive" type="checkbox" name="food-sensitivities[]" value="additive" /> <label for="food_sensitivities_additive">Food additive intolerance</label><br />
                        <input id="food_sensitivities_sulfite" type="checkbox" name="food-sensitivities[]" value="sulfite" /> <label for="food_sensitivities_sulfite">Sulfite intolerance</label><br />
                        <input id="food_sensitivities_nightshade" type="checkbox" name="food-sensitivities[]" value="nightshade" /> <label for="food_sensitivities_nightshade">Nightshade intolerance</label><br />
                        <input id="food_sensitivities_fructose" type="checkbox" name="food-sensitivities[]" value="fructose" /> <label for="food_sensitivities_fructose">Fructose, fructan, polyol intolerance</label><br />
                        <input id="food_sensitivities_other" type="checkbox" name="food-sensitivities[]" value="other" class="checkbox-unhide" data-unhide-id="food-sensitivities-other" /> <label for="food_sensitivities_other">Other</label>
                        <div id="food-sensitivities-other" class="other-hidden">
                            <div class="input-note">Please specify:</div>
                            <div class="input-field"><input type="text" name="food-sensitivities-other"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 4: Supplements</h2>

            <div class="intake-question">
                <div class="input-text">Are you now using any nutritional supplements? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="supplements_yes" type="radio" name="supplements-yesno" value="yes" class="radio-unhide" data-unhide-id="supplements-yes" /> <label for="supplements_yes">Yes</label><br />
                    <input id="supplements_no" type="radio" name="supplements-yesno" value="no" class="radio-hide" data-hide-id="supplements-yes" /> <label for="supplements_no">No</label>
                </div>
            </div>

            <div id="supplements-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Which supplements do you now regularly take? <span class="required">*</span></div>
                    <div class="input-note">Check all that apply:</div>
                    <div class="input-field checkbox">
                        <input id="supplements_multivitamin" type="checkbox" name="supplements[]" value="multivitamin" /> <label for="supplements_multivitamin">Multivitamin / multimineral</label><br />
                        <input id="supplements_fish_oil" type="checkbox" name="supplements[]" value="fish-oil" /> <label for="supplements_fish_oil">Fish oil</label><br />
                        <input id="supplements_omega3" type="checkbox" name="supplements[]" value="omega3" /> <label for="supplements_omega3">Other omega 3 supplement</label><br />
                        <input id="supplements_protein" type="checkbox" name="supplements[]" value="protein" /> <label for="supplements_protein">Protein powder</label><br />
                        <input id="supplements_probiotics" type="checkbox" name="supplements[]" value="probiotics" /> <label for="supplements_probiotics">Probiotics</label><br />
                        <input id="supplements_enzymes" type="checkbox" name="supplements[]" value="enzymes" /> <label for="supplements_enzymes">Digestive enzymes</label><br />
                        <input id="supplements_calcium" type="checkbox" name="supplements[]" value="calcium" /> <label for="supplements_calcium">Calcium</label><br />
                        <input id="supplements_vitaminD" type="checkbox" name="supplements[]" value="vitaminD" /> <label for="supplements_vitaminD">Vitamin D</label><br />
                        <input id="supplements_workout_drinks" type="checkbox" name="supplements[]" value="workout-drinks" /> <label for="supplements_workout_drinks">Pre, during, or post-workout drinks</label><br />
                        <input id="supplements_bcaa" type="checkbox" name="supplements[]" value="bcaa" /> <label for="supplements_bcaa">Branch chain amino acids (BCAAs)</label><br />
                        <input id="supplements_creatine" type="checkbox" name="supplements[]" value="creatine" /> <label for="supplements_creatine">Creatine</label><br />
                        <input id="supplements_other_vitamins" type="checkbox" name="supplements[]" value="other-vitamins" class="checkbox-unhide" data-unhide-id="vitamins-other" /> <label for="supplements_other_vitamins">Other individual vitamins or minerals</label><br />
                        <div id="vitamins-other" class="other-hidden">
                            <div class="input-note">Please specify:</div>
                            <div class="input-field"><input type="text" name="vitamins-other"></div>
                        </div>
                        <input id="supplements_other_supplements" type="checkbox" name="supplements[]" value="other-supplements" class="checkbox-unhide" data-unhide-id="supplements-other" /> <label for="supplements_other_supplements">Other supplement(s) not listed here</label>
                        <div id="supplements-other" class="other-hidden">
                            <div class="input-note">Please specify:</div>
                            <div class="input-field"><input type="text" name="supplements-other"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 5: Nutrients & water</h2>

            <div class="intake-question">
                <div class="input-text">Currently, how many glasses of water do you drink on an average day? <span class="required">*</span></div>
                <div class="input-field select">
                    <select id="water-intake" name="water-intake">
                        <option value="0-2">0-2 glasses</option>
                        <option value="3-5">3-5 glasses</option>
                        <option value="6-8">6-8 glasses</option>
                        <option value="8+">8+ glasses</option>
                    </select>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, on an average day, how many of your meals now include at least a palm-sized portion of protein (like meat, fish, dairy, eggs, or other protein dense plant foods)? <span class="required">*</span></div>
                <div class="input-field select">
                    <select id="protein-intake" name="protein-intake">
                        <option value="0-1">0-1 meals</option>
                        <option value="2-3">2-3 meals</option>
                        <option value="4+">4+ meals</option>
                    </select>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Currently, on an average day, how many of your meals now include at least one serving of colorful fruit or vegetables? <span class="required">*</span></div>
                <div class="input-field select">
                    <select id="vegetable-intake" name="vegetable-intake">
                        <option value="0-1">0-1 meals</option>
                        <option value="2-3">2-3 meals</option>
                        <option value="4+">4+ meals</option>
                    </select>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">On a scale of 1-10, how knowledgeable are you now about making smart food choices?<br />(1 = I know very little; 10 = I'm a nutrition expert) <span class="required">*</span></div>
                <div class="input-field">
                    <div class="nutrition-knowledge-errormessage"></div>
                    <div class="radio-scale-table">
                        <div class="radio-scale-row">
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-1" name="nutrition-knowledge" value="1" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-2" name="nutrition-knowledge" value="2" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-3" name="nutrition-knowledge" value="3" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-4" name="nutrition-knowledge" value="4" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-5" name="nutrition-knowledge" value="5" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-6" name="nutrition-knowledge" value="6" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-7" name="nutrition-knowledge" value="7" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-8" name="nutrition-knowledge" value="8" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-9" name="nutrition-knowledge" value="9" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-knowledge-10" name="nutrition-knowledge" value="10" /></div>
                        </div>
                        <div class="radio-scale-row">
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-1">1<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-2">2<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-3">3<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-4">4<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-5">5<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-6">6<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-7">7<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-8">8<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-9">9<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-knowledge-10">10<label></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">On a scale of 1-10, how confident are you that you can make smart food choices consistently?<br />(1 = Not at all confident; 10 = Totally confident) <span class="required">*</span></div>
                <div class="input-field">
                    <div class="nutrition-confidence-errormessage"></div>
                    <div class="radio-scale-table">
                        <div class="radio-scale-row">
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-1" name="nutrition-confidence" value="1" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-2" name="nutrition-confidence" value="2" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-3" name="nutrition-confidence" value="3" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-4" name="nutrition-confidence" value="4" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-5" name="nutrition-confidence" value="5" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-6" name="nutrition-confidence" value="6" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-7" name="nutrition-confidence" value="7" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-8" name="nutrition-confidence" value="8" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-9" name="nutrition-confidence" value="9" /></div>
                            <div class="radio-scale-cell"><input type="radio" id="nutrition-confidence-10" name="nutrition-confidence" value="10" /></div>
                        </div>
                        <div class="radio-scale-row">
                            <div class="radio-scale-cell"><label for="nutrition-confidence-1">1<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-2">2<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-3">3<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-4">4<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-5">5<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-6">6<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-7">7<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-8">8<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-9">9<label></div>
                            <div class="radio-scale-cell"><label for="nutrition-confidence-10">10<label></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 6: Your health</h2>

            <div class="intake-question">
                <div class="input-text">Do you currently have any diagnosed health problems? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="medical_issues_yes" type="radio" name="medical-issues-yesno" value="yes" class="radio-unhide" data-unhide-id="medical-issues-yes,other-treatments-yesno" /> <label for="medical_issues_yes">Yes</label><br />
                    <input id="medical_issues_no" type="radio" name="medical-issues-yesno" value="no" class="radio-hide" data-hide-id="medical-issues-yes,other-treatments-yesno" /> <label for="medical_issues_no">No</label>
                </div>
            </div>

            <div id="medical-issues-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Please list your medical issues: <span class="required">*</span></div>
                    <div class="input-field"><textarea name="medical-issues"></textarea></div>
                </div>
            </div>

            <div id="other-treatments-yesno" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Are you receiving any other treatments for the health concerns above? <span class="required">*</span></div>
                    <div class="input-field radio">
                        <input id="other_treatments_yes" type="radio" name="other-treatments-yesno" value="yes" class="radio-unhide" data-unhide-id="other-treatments-details" /> <label for="other_treatments_yes">Yes</label><br />
                        <input id="other_treatments_no" type="radio" name="other-treatments-yesno" value="no" class="radio-hide" data-hide-id="other-treatments-details" /> <label for="other_treatments_no">No</label>
                    </div>
                </div>

                <div id="other-treatments-details" class="question-hidden">
                    <div class="intake-question">
                        <div class="input-text">What other treatments are you receiving? <span class="required">*</span></div>
                        <div class="input-field"><textarea name="other-treatments"></textarea></div>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Are you currently on any prescription medications? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="medications_yes"type="radio" name="medications-yesno" value="yes" class="radio-unhide" data-unhide-id="medications-yes" /> <label for="medications_yes">Yes</label><br />
                    <input id="medications_no"type="radio" name="medications-yesno" value="no" class="radio-hide" data-hide-id="medications-yes" /> <label for="medications_no">No</label>
                </div>
            </div>

            <div id="medications-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Which prescription medications do you regularly take? <span class="required">*</span></div>
                    <div class="input-note">Check all that apply:</div>
                    <div class="input-field checkbox">

                        <input id="medications_anti_hypertensive" type="checkbox" name="medications[]" value="anti-hypertensive" /> <label for="medications_anti_hypertensive">Anti-hypertensive</label><br />
                        <input id="medications_statin" type="checkbox" name="medications[]" value="statin" /> <label for="medications_statin">Statin</label><br />
                        <input id="medications_antidepressant" type="checkbox" name="medications[]" value="antidepressant" /> <label for="medications_antidepressant">Antidepressant/Antianxiety</label><br />
                        <input id="medications_insulin" type="checkbox" name="medications[]" value="insulin" /> <label for="medications_insulin">Insulin/glucose management</label><br />
                        <input id="medications_stomach" type="checkbox" name="medications[]" value="stomach" /> <label for="medications_stomach">Stomach - PPI/GERD</label><br />
                        <input id="medications_aspirin" type="checkbox" name="medications[]" value="aspirin" /> <label for="medications_aspirin">Aspirin</label><br />
                        <input id="medications_beta_blocker" type="checkbox" name="medications[]" value="beta-blocker" /> <label for="medications_beta_blocker">Beta blocker</label><br />
                        <input id="medications_antihistamine" type="checkbox" name="medications[]" value="antihistamine" /> <label for="medications_antihistamine">Allergy/asthma (antihistamine)</label><br />
                        <input id="medications_thyroid" type="checkbox" name="medications[]" value="thyroid" /> <label for="medications_thyroid">Thyroid medication</label><br />
                        <input id="medications_beta_agonist" type="checkbox" name="medications[]" value="beta-agonist" /> <label for="medications_beta_agonist">Allergy/asthma (beta agonist)</label><br />
                        <input id="medications_testosterone" type="checkbox" name="medications[]" value="testosterone" /> <label for="medications_testosterone">Hormones (testosterone)</label><br />
                        <input id="medications_hyperlipidemia" type="checkbox" name="medications[]" value="hyperlipidemia" /> <label for="medications_hyperlipidemia">Hyperlipidemia</label><br />
                        <input id="medications_nsaid" type="checkbox" name="medications[]" value="nsaid" /> <label for="medications_nsaid">NSAID</label><br />
                        <input id="medications_allergy_corticosteroid" type="checkbox" name="medications[]" value="allergy-corticosteroid" /> <label for="medications_allergy_corticosteroid">Allergy/asthma (corticosteroid)</label><br />
                        <input id="medications_gout" type="checkbox" name="medications[]" value="gout" /> <label for="medications_gout">Gout medication</label><br />

                        <input id="medications_other" type="checkbox" name="medications[]" value="other" class="checkbox-unhide" data-unhide-id="medications-other" /> <label for="medications_other">Other</label>
                        <div id="medications-other" class="other-hidden">
                            <div class="input-note">Please specify:</div>
                            <div class="input-field"><input type="text" name="medications-other"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 7: Your work</h2>

            <div class="intake-question">
                <div class="input-text">Were you employed (paid work) during coaching? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="employed_yes" type="radio" name="employed-yesno" value="yes" class="radio-unhide radio-hide" data-unhide-id="work-yes" /> <label for="employed_yes">Yes</label><br />
                    <input id="employed_no" type="radio" name="employed-yesno" value="no" class="radio-unhide radio-hide" data-hide-id="work-yes" /> <label for="employed_no">No</label>
                </div>
            </div>

            <div id="work-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">What time of day did you primarily work? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="work_shifts_daytime" type="radio" name="work-shifts" value="daytime" /> <label for="work_shifts_daytime">Mostly daytime hours</label><br />
                        <input id="work_shifts_nighttime" type="radio" name="work-shifts" value="nighttime" /> <label for="work_shifts_nighttime">Mostly nighttime hours</label><br />
                        <input id="work_shifts_rotating" type="radio" name="work-shifts" value="rotating" /> <label for="work_shifts_rotating">I have a rotating schedule and do shift work</label>
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">How many hours did you work on most days? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="work_hours_4_6" type="radio" name="work-hours" value="4-6" /> <label for="work_hours_4_6">4-6 hours</label><br />
                        <input id="work_hours_6_8" type="radio" name="work-hours" value="6-8" /> <label for="work_hours_6_8">6-8 hours</label><br />
                        <input id="work_hours_8_10" type="radio" name="work-hours" value="8-10" /> <label for="work_hours_8_10">8-10 hours</label><br />
                        <input id="work_hours_10_12" type="radio" name="work-hours" value="10-12" /> <label for="work_hours_10_12">10-12 hours</label><br />
                        <input id="work_hours_12_plus" type="radio" name="work-hours" value="12+" /> <label for="work_hours_12_plus">12+ hours</label><br />
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">What was your activity level at work? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="work_activity_inactive" type="radio" name="work-activity" value="inactive" /> <label for="work_activity_inactive">Inactive; I'm mostly sitting</label><br />
                        <input id="work_activity_moderate" type="radio" name="work-activity" value="moderate" /> <label for="work_activity_moderate">Moderate; I'm on my feet for a part of every day</label><br />
                        <input id="work_activity_active" type="radio" name="work-activity" value="active" /> <label for="work_activity_active">Active; I'm moving all day</label><br />
                        <input id="work_activity_very_active" type="radio" name="work-activity" value="very-active" /> <label for="work_activity_very_active">Very active; I do physical labor</label>
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">What was your typical stress level at work? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="work_stress_low" type="radio" name="work-stress" value="low" /> <label for="work_stress_low">Low stress; my work is pretty relaxed</label><br />
                        <input id="work_stress_moderate" type="radio" name="work-stress" value="moderate" /> <label for="work_stress_moderate">Moderate stress; it's sometimes relaxed and sometimes crazy</label><br />
                        <input id="work_stress_high" type="radio" name="work-stress" value="high" /> <label for="work_stress_high">High stress; I'm always under pressure demands</label>
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">How often did you travel for work? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="work_travel_never" type="radio" name="work-travel" value="never" /> <label for="work_travel_never">Never</label><br />
                        <input id="work_travel_rarely" type="radio" name="work-travel" value="rarely" /> <label for="work_travel_rarely">Not very often; a few times a year</label><br />
                        <input id="work_travel_moderate" type="radio" name="work-travel" value="moderately" /> <label for="work_travel_moderate">Moderately; at least once every few months</label><br />
                        <input id="work_travel_often" type="radio" name="work-travel" value="often" /> <label for="work_travel_often">Often; at least once every month</label><br />
                        <input id="work_travel_very_often" type="radio" name="work-travel" value="very-often" /> <label for="work_travel_very_often">Very often; at least once a week</label>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Were you a student during coaching? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="student_yes" type="radio" name="student-yesno" value="yes" class="radio-unhide" data-unhide-id="student-yes" /> <label for="student_yes">Yes</label><br />
                    <input id="student_no" type="radio" name="student-yesno" value="no" class="radio-hide" data-hide-id="student-yes" /> <label for="student_no">No</label>
                </div>
            </div>

            <div id="student-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">What was your typical stress level at school? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="student_stress_low" type="radio" name="student-stress" value="low" /> <label for="student_stress_low">Low stress; my work is pretty relaxed</label><br />
                        <input id="student_stress_moderate" type="radio" name="student-stress" value="moderate" /> <label for="student_stress_moderate">Moderate stress; it's sometimes relaxed and sometimes crazy</label><br />
                        <input id="student_stress_high" type="radio" name="student-stress" value="high" /> <label for="student_stress_high">High stress; I'm always under pressure demands</label>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">Were you a primary caregiver for children, individuals with a disability, or an elderly relative during coaching? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="caregiver_yes" type="radio" name="caregiver-yesno" value="yes" class="radio-unhide" data-unhide-id="caregiver-yes" /> <label for="caregiver_yes">Yes</label><br />
                    <input id="caregiver_no" type="radio" name="caregiver-yesno" value="no" class="radio-hide" data-hide-id="caregiver-yes" /> <label for="caregiver_no">No</label>
                </div>
            </div>

            <div id="caregiver-yes" class="question-hidden">
                <div class="intake-question">
                    <div class="input-text">Who were you a primary caregiver to? <span class="required">*</span></div>
                    <div class="input-note">Check all that apply:</div>
                    <div class="input-field checkbox">
                        <input id="caregiver_elderly" type="checkbox" name="caregiver[]" value="elderly" /> <label for="caregiver_elderly">Parent or grandparent</label><br />
                        <input id="caregiver_partner" type="checkbox" name="caregiver[]" value="partner" /> <label for="caregiver_partner">Spouse or partner</label><br />
                        <input id="caregiver_children" type="checkbox" name="caregiver[]" value="children" /> <label for="caregiver_children">Child or children</label><br />
                        <input id="caregiver_friend" type="checkbox" name="caregiver[]" value="friend" /> <label for="caregiver_friend">Friend</label><br />
                    </div>
                </div>

                <div class="intake-question">
                    <div class="input-text">What was your typical stress level from caregiving? <span class="required">*</span></div>
                    <div class="input-note">Choose the answer that best applies.</div>
                    <div class="input-field radio">
                        <input id="caregiver_stress_low" type="radio" name="caregiver-stress" value="low" /> <label for="caregiver_stress_low">Low stress; my work is pretty relaxed</label><br />
                        <input id="caregiver_stress_moderate" type="radio" name="caregiver-stress" value="moderate" /> <label for="caregiver_stress_moderate">Moderate stress; it's sometimes relaxed and sometimes crazy</label><br />
                        <input id="caregiver_stress_high" type="radio" name="caregiver-stress" value="high" /> <label for="caregiver_stress_high">High stress; I'm always under pressure demands</label>
                    </div>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">What was your typical stress level at home during coaching? <span class="required">*</span></div>
                <div class="input-note">Choose the answer that best applies.</div>
                <div class="input-field radio">
                    <input id="home_stress_low" type="radio" name="home-stress" value="low" /> <label for="home_stress_low">Low stress; it's pretty relaxed</label><br />
                    <input id="home_stress_moderate" type="radio" name="home-stress" value="moderate" /> <label for="home_stress_moderate">Moderate stress; it's sometimes relaxed and sometimes crazy</label><br />
                    <input id="home_stress_high" type="radio" name="home-stress" value="high" /> <label for="home_stress_high">High stress; I'm always under pressure demands</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">For all your lifestyle-related stressors, how well did you cope with the stress? <span class="required">*</span></div>
                <div class="input-note">Choose the answer that best applies.</div>
                <div class="input-field radio">
                    <input id="coping_stress_low" type="radio" name="coping-stress" value="low" /> <label for="coping_stress_low">My stress is pretty low and I do great when faced with most stressors.</label><br />
                    <input id="coping_stress_moderate" type="radio" name="coping-stress" value="moderate" /> <label for="coping_stress_moderate">Although sometimes taxing, I have strategies for staying calm and focused.</label><br />
                    <input id="coping_stress_high" type="radio" name="coping-stress" value="high" /> <label for="coping_stress_high">I sometimes feel on the brink of a breakdown and struggle with stress management.</label>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 8: Alcohol, smoking & other drugs?</h2>

            <div class="intake-question">
                <div class="input-text">How often do you currently drink alcohol? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="alcohol_frequency_never" type="radio" name="alcohol-frequency" value="never" /> <label for="alcohol_frequency_never">Never</label><br />
                    <input id="alcohol_frequency_rarely" type="radio" name="alcohol-frequency" value="rarely" /> <label for="alcohol_frequency_rarely">Rarely; a few times a year</label><br />
                    <input id="alcohol_frequency_moderately" type="radio" name="alcohol-frequency" value="moderately" /> <label for="alcohol_frequency_moderately">Moderately; a few times a month</label><br />
                    <input id="alcohol_frequency_regularly" type="radio" name="alcohol-frequency" value="regularly" /> <label for="alcohol_frequency_regularly">Regularly; a few times a week</label><br />
                    <input id="alcohol_frequency_often" type="radio" name="alcohol-frequency" value="often" /> <label for="alcohol_frequency_often">Daily; one or two drinks</label><br />
                    <input id="alcohol_frequency_excessive" type="radio" name="alcohol-frequency" value="excessive" /> <label for="alcohol_frequency_excessive">Daily; more than two drinks</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">How often do you currently smoke cigarettes? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="smoking_frequency_never" type="radio" name="smoking-frequency" value="never" /> <label for="smoking_frequency_never">Never</label><br />
                    <input id="smoking_frequency_rarely" type="radio" name="smoking-frequency" value="rarely" /> <label for="smoking_frequency_rarely">Rarely</label><br />
                    <input id="smoking_frequency_moderately" type="radio" name="smoking-frequency" value="moderately" /> <label for="smoking_frequency_moderately">Moderately</label><br />
                    <input id="smoking_frequency_heavy" type="radio" name="smoking-frequency" value="heavy" /> <label for="smoking_frequency_heavy">Heavy</label>
                </div>
            </div>

            <div class="intake-question">
                <div class="input-text">How often do you currently use non-prescription drugs? <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="drugs_frequency_never" type="radio" name="drugs-frequency" value="never" /> <label for="drugs_frequency_never">Never</label><br />
                    <input id="drugs_frequency_rarely" type="radio" name="drugs-frequency" value="rarely" /> <label for="drugs_frequency_rarely">Rarely; a few times a year</label><br />
                    <input id="drugs_frequency_moderately" type="radio" name="drugs-frequency" value="moderately" /> <label for="drugs_frequency_moderately">Moderately; a few times a month</label><br />
                    <input id="drugs_frequency_often" type="radio" name="drugs-frequency" value="often" /> <label for="drugs_frequency_often">Daily</label>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Part 9: Consent</h2>
            <p>At Precision Nutrition, we're dedicated to finding and using the best possible evidence- and research-based nutrition practices. Because of that mission, we often publish data from our coaching program (anonymously, of course) in medical and scientific journals.</p>
            <p>But, before doing so, we like to get your consent to include your data in current and future publications. Therefore...</p>
            <p>"I agree to let Precision Nutrition use the information they collect in this program in current and future research studies. I understand that:</p>
            <p>* <b>my data will be completely anonymous</b> and I'll never be able to be identified from it</p>
            <p>* <b>data will be used mostly in aggregate form</b> - in other words, to focus on and analyze a large population of thousands of clients, rather than single specific individuals or unique personal details</p>
            <p>* <b>there are no risks associated with saying yes</b></p>
            <p>* by allowing my information to be used in this way, I'll be helping further the field of exercise and nutrition science."</p>
            <p>(Note: It's completely fine to choose "no" here. However, by doing so, your data are excluded from our analyses and won't have a chance to positively affect the health and fitness community.)</p>

            <div class="intake-question">
                <div class="input-text">I consent to include my data for research projects. <span class="required">*</span></div>
                <div class="input-field radio">
                    <input id="research_yes"yes type="radio" name="research-yesno" value="1" /> <label for="research_yes">Yes</label><br />
                    <input id="research_no" type="radio" name="research-yesno" value="0" /> <label for="research_no">No</label>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Review and submit</h2>
            <p>Okay, that's it. Please take a minutes to make sure all your answers are complete and accurate.</p>
            <button class="button" type="submit">Finalize and Send!</button>
        </div>
    </form>

    <!-- END TEMPLATE: cp_exit_survey_edit -->
</div>
<script type="text/javascript">
    var INTAKEANSWERS = {};
</script>
<script type="text/javascript" src="//use.typekit.net/ioa5hqc.js"></script>
<script type="text/javascript">try{Typekit.load();}catch(e){}</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript" src="js/cp-exit-survey.js"></script>
<script type="text/javascript">window.NREUM||(NREUM={});NREUM.info={"beacon":"beacon-5.newrelic.com","licenseKey":"cb7e744beb","applicationID":"3592826","transactionName":"NAFUYhFRXkdYV00PXQ1LY0QKH11RVFZcFEFMB0YbBkhZQBRHTBREBh0YRgtA","queueTime":0,"applicationTime":94,"atts":"GEZXFFlLTUk=","errorBeacon":"bam.nr-data.net","agent":"js-agent.newrelic.com\/nr-476.min.js"}</script></body>
</html>
<!-- END TEMPLATE: cp_exit_survey -->