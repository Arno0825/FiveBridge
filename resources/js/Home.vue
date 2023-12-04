<script setup>
import {ref} from "vue";
import background from '../pokerImage/background.png';
import diamond_A from '../pokerImage/diamond_A.jpg';
import heart_A from '../pokerImage/heart_A.jpg';
import spade_A from '../pokerImage/spade_A.jpg';
import club_A from '../pokerImage/club_A.jpg';

import diamond_2 from '../pokerImage/diamond_2.jpg';
import heart_2 from '../pokerImage/heart_2.jpg';
import spade_2 from '../pokerImage/spade_2.jpg';
import club_2 from '../pokerImage/club_2.jpg';

import diamond_3 from '../pokerImage/diamond_3.jpg';
import heart_3 from '../pokerImage/heart_3.jpg';
import spade_3 from '../pokerImage/spade_3.jpg';
import club_3 from '../pokerImage/club_3.jpg';

import diamond_4 from '../pokerImage/diamond_4.jpg';
import heart_4 from '../pokerImage/heart_4.jpg';
import spade_4 from '../pokerImage/spade_4.jpg';
import club_4 from '../pokerImage/club_4.jpg';

import diamond_5 from '../pokerImage/diamond_5.jpg';
import heart_5 from '../pokerImage/heart_5.jpg';
import spade_5 from '../pokerImage/spade_5.jpg';
import club_5 from '../pokerImage/club_5.jpg';


const riverCard = ref([
    "/meme/cantBad.png",
    "/meme/CatchBad.png",
    "/meme/cantBadCat.png",
    "/meme/koBad.png",
    "/meme/enoughBad.png",
    "/meme/youBad.png",
    "/meme/noBadMan.png",
    "/meme/suprise.png",
    "/meme/gone.jpg",
    "/meme/BadBird.png"
]);

const lose = ref([
    "/meme/youBad.png",
    "/meme/lose.png",
    "/meme/youLose.png",
    "/meme/loseHan.png",
    "/meme/paLose.png",
    "/meme/dogLose.png",
    "/meme/catLose.png",
    "/meme/alreadyLose.png",
]);

const win = ref([
    "/meme/youWin.png",
    "/meme/winLee.png",
    "/meme/alreadyWin.png",
]);

const character = ref('player'); //observer

const selectedCharacter = ref('player');

const name = ref('');

const isKing = ref(false);

const place = ref('');

const player = ref();

const isBidder = ref(false);

const isFirstBidder = ref(false);

const biddingCheck = ref(false);

const passCount = ref(0);

const round = ref(1);

const riverCardsOpenCount = ref(0);

const ablePlay = ref(false)

const biddingList = ref([]);

const loadingStates = {
    place_1: ref(false),
    place_2: ref(false),
    place_3: ref(false),
    place_4: ref(false),
    place_5: ref(false)
};

const king = {
    place_1: ref(false),
    place_2: ref(false),
    place_3: ref(false),
    place_4: ref(false),
    place_5: ref(false)
};

const roundCard = ref([])
const handCards = ref([]);

const wsServer = 'wss://3533-2001-b011-8019-f30f-ac14-1a47-9118-3754.ngrok-free.app'

// const wsServer = 'ws://127.0.0.1:9502'

const websocket = new WebSocket(wsServer);
//onopen监听连接打开
websocket.onopen = function (evt) {
    document.getElementById("characterSelectForm").classList.remove('hidden')
};
websocket.onerror = function (event) {
    console.error('WebSocket Error: ' + event.message);
};
websocket.onmessage = function (msg) {
    const data = JSON.parse(msg.data);
    switch (data['action']) {
        case 'character':
            place.value = data['place']
            if (data['place'] === 'observer') {
                character.value = 'observer'
            } else {
                character.value = 'player'
            }
            document.getElementById("characterSelectForm").classList.add('hidden')
            document.getElementById("characterLoading").classList.remove('hidden')
            break;
        case 'allCharacter':
            setAllPlayerName(data['data'])
            break;
        case 'ready':
            document.getElementById("characterLoading").classList.add('hidden')
            document.getElementById("dashboard").classList.remove('hidden')
            break;
        case 'hands':
            document.getElementById("dashboard").classList.add('hidden')
            document.getElementById("characterLoading").classList.add('hidden')
            handCards.value = data['hands'];
            deal()
            break;
        case 'rivers':
            riverCard.value = data['rivers'];
            isKing.value = true;
            break;
        case 'bidding':
            setBidding(data['place'], data['card'])
            break;
        case 'bidder':
            setBidder(data['place'], data['first']);
            break;
        case 'biddingCheck':
            setBidder(data['place'], data['first']);
            biddingCheck.value = true
            break;
        case 'pending':
            document.getElementById("choose_king").classList.add('hidden')
            document.getElementById("river_cards").classList.remove('hidden');
            setKing(data['place'], data['result'])
            break;
        case 'kingShuffle':
            handCards.value = data['hands'];
            riverCard.value = [];
            document.getElementById("riverCard1").src = background;
            document.getElementById("riverCard2").src = background;
            document.getElementById("riverCard3").src = background;
            document.getElementById("river_cards").classList.add('hidden');
            document.getElementById("place_1_hands").innerHTML = '';
            kingShuffleAnimation();
            break;
        case 'confirmShuffle':
            handCards.value = data['hands'];
            riverCard.value = [];
            document.getElementById("place_1_hands").innerHTML = '';
            document.getElementById("confirmChange").classList.add('hidden');
            confirmShuffle();
            break;
        case'start':
            document.getElementById("river_cards").classList.add('hidden');
            document.getElementById("table").classList.remove('hidden');
            break;
        case 'playLoading':
            playLoading(data['place']);
            break;
        case 'play':
            roundCard.value.push(data['card'])
            generatePlayCard(data['place'], data['card']);
            removeHandsCard(data['place'])
            break;
        case 'winRound':
            roundCard.value = [];
            winRound(data['place'])
            break;
        case 'final':
            showFinal(data['result'])
            break;
        case 'playerClose':
            (async () => {
                await clear()
            })()
            break;
    }
};
const kingShuffleAnimation = async () => {
    const place1 = document.getElementById("place_1_hands");
    const generateKingPlaceCard = (card) => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const cardDiv = document.createElement("div");
                cardDiv.classList.add("-mr-3");
                cardDiv.id = card;

                const cardImage = document.createElement("img");
                cardImage.src = "/pokerImage/" + card + '.jpg';

                cardDiv.addEventListener('click', () => {
                    changeCard(card)
                });

                cardDiv.appendChild(cardImage);

                place1.appendChild(cardDiv);
                resolve('generateKingPlaceCard')
            }, 50)
        });
    }
    for (let i = 0; i < 13; i++) {
        await generateKingPlaceCard(handCards.value[i]);
    }
};

const winRound = async (player) => {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            console.log(player)
            const compare = placeCompare();
            const place = compare[player];
            console.log(place)

            const div = document.getElementById(place + '_point')
            console.log(place + '_point')

            console.log(place)

            let src = background
            if (place === 'place_2' || place === 'place_5') {
                src = "/pokerImage/background_90.png"
            }
            const img = document.createElement('img')
            img.src = src;
            let h = 'h-10'
            if (place === 'place_1') {
                h = 'h-9'
            }
            img.classList.add(h)
            div.appendChild(img)

            playLoading(player)
            document.getElementById('table').innerHTML = '';
            resolve('winRound')
        }, 2000)
    });
}

const confirmShuffle = async () => {
    const place1 = document.getElementById("place_1_hands");
    const generateConfirmPlaceCard = (card) => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const cardDiv = document.createElement("div");
                cardDiv.classList.add("-mr-3");
                cardDiv.id = card;

                const cardImage = document.createElement("img");
                cardImage.src = "/pokerImage/" + card + '.jpg';

                cardDiv.addEventListener('click', () => {
                    playCard(card)
                });

                cardDiv.appendChild(cardImage);

                place1.appendChild(cardDiv);
                resolve('generateConfirmPlaceCard')
            }, 50)
        });
    }
    for (let i = 0; i < 10; i++) {
        await generateConfirmPlaceCard(handCards.value[i]);
    }
};

const requestDeal = () => {
    const data = {'action': 'deal'}
    websocket.send(JSON.stringify(data));
}

const deal = async () => {
    const place1 = document.getElementById("place_1_hands");
    const place2 = document.getElementById("place_2_hands");
    const place3 = document.getElementById("place_3_hands");
    const place4 = document.getElementById("place_4_hands");
    const place5 = document.getElementById("place_5_hands");
    const riverCards = document.getElementById("river_cards");
    const chooseKing = document.getElementById("choose_king");
    const generatePlace1Card = (card) => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const cardDiv = document.createElement("div");
                cardDiv.classList.add("-mr-3");
                cardDiv.id = card;

                const cardImage = document.createElement("img");
                cardImage.src = "/pokerImage/" + card + '.jpg';

                cardDiv.addEventListener('click', () => {
                    playCard(card)
                });

                cardDiv.appendChild(cardImage);

                place1.appendChild(cardDiv);
                resolve('generatePlace1Card')
            }, 50)
        });
    };

    const generatePlace2Card = () => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const card = document.createElement("div");
                card.classList.add("-mb-10");
                card.classList.add("flex");

                const cardImage = document.createElement("img");
                cardImage.src = "/pokerImage/background_90.png";
                card.appendChild(cardImage);

                place2.appendChild(card);

                resolve('generatePlace2Card')
            }, 50)
        });
    }

    const generatePlace3Card = () => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const card = document.createElement("div");
                card.classList.add("-mr-5");

                const cardImage = document.createElement("img");
                cardImage.src = background;

                card.appendChild(cardImage);

                place3.appendChild(card);
                resolve('generatePlace3Card')
            }, 50)
        });
    }

    const generatePlace4Card = () => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const card = document.createElement("div");
                card.classList.add("-mr-5");

                const cardImage = document.createElement("img");
                cardImage.src = background;

                card.appendChild(cardImage);

                place4.appendChild(card);
                resolve('generatePlace4Card')
            }, 50)
        });
    }

    const generatePlace5Card = () => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const card = document.createElement("div");
                card.classList.add("-mb-10");
                card.classList.add("flex");
                card.classList.add("justify-end");
                card.classList.add("items-center");

                const cardImage = document.createElement("img");
                cardImage.src = "/pokerImage/background_90.png";
                card.appendChild(cardImage);

                place5.appendChild(card);
                resolve('generatePlace5Card')
            }, 50)
        });
    }

    for (let i = 0; i < 10; i++) {
        await generatePlace1Card(handCards.value[i]);
        await generatePlace2Card();
        await generatePlace3Card();
        await generatePlace4Card();
        await generatePlace5Card();
    }
    if (character.value === 'observer') {
        riverCards.classList.remove('hidden')
    }
    if (character.value === 'player') {
        chooseKing.classList.remove('hidden')
    }
}
const checkRiverCard = (num) => {
    if (character.value === 'observer' || isKing.value === true) {
        const card = document.getElementById("riverCard" + num);
        card.src = "/pokerImage/" + riverCard.value[num - 1] + '.jpg'
        if (isKing.value === true) {
            riverCardsOpenCount.value = riverCardsOpenCount.value + 1;
        }
        if (riverCardsOpenCount.value === 3 && character.value !== 'observer') {
            kingShuffleRequest();
        }
        return;
    }

    const randomIndex = Math.floor(Math.random() * riverCard.value.length);
    const card = document.getElementById("riverCard" + num);
    card.src = riverCard.value[randomIndex]
}

const kingShuffleRequest = async () => {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            let data = {
                'action': 'kingShuffle',
                'place': place.value,
            }
            websocket.send(JSON.stringify(data));

            resolve('stop')
        }, 2000)
    });
}


const characterConfirm = () => {
    const data = {
        'action': 'character',
        'character': selectedCharacter.value,
        'name': name.value,
    }
    websocket.send(JSON.stringify(data));
}

const bidding = (card) => {
    if (isBidder.value === false) {
        return
    }
    let able = true
    if (biddingList.value.length === 0) {
        if (card !== 'club_1' && card !== 'diamond_1' && card !== 'heart_1' && card !== 'spade_1') {
            able = false
        }
    }
    biddingList.value.forEach(function (biddingCard) {
        if (biddingCard === card) {
            able = false
        }
    });
    if (biddingList.value.length !== 0 && card !== 'pass') {
        const lastCard = biddingList.value[biddingList.value.length - 1]
        const lastCardSuits = lastCard.split('_')[0]
        const lastCardNumber = parseInt(lastCard.split('_')[1])
        let allowCard = [];
        if (lastCardSuits === 'club') {
            allowCard.push('diamond_' + lastCardNumber)
            allowCard.push('heart_' + lastCardNumber)
            allowCard.push('spade_' + lastCardNumber)
        }
        if (lastCardSuits === 'diamond') {
            allowCard.push('club_' + (lastCardNumber + 1))
            allowCard.push('heart_' + lastCardNumber)
            allowCard.push('spade_' + lastCardNumber)
        }
        if (lastCardSuits === 'heart') {
            allowCard.push('club_' + (lastCardNumber + 1))
            allowCard.push('diamond_' + (lastCardNumber + 1))
            allowCard.push('spade_' + lastCardNumber)
        }
        if (lastCardSuits === 'spade') {
            allowCard.push('club_' + (lastCardNumber + 1))
            allowCard.push('diamond_' + (lastCardNumber + 1))
            allowCard.push('heart_' + (lastCardNumber + 1))
        }
        if (!allowCard.includes(card)) {
            able = false
        }
    }
    if (biddingList.value.length === 1 && biddingList.value[0] === 'club_1' && card === 'club_1') {
        able = true
    }
    if (card === 'pass') {
        able = true
    }
    if (isBidder.value && able) {
        let check = 'pass'
        if (card !== 'pass') {
            check = card.split('_')[1] + ' ' + card.split('_')[0]
        }
        if (confirm('確定喊' + check + '?')) {
            isBidder.value = false
            let data = {
                'action': 'bidding',
                'place': place.value,
                'card': card
            }

            if (biddingCheck.value) {
                data = {
                    'action': 'biddingCheck',
                    'place': place.value,
                    'card': card
                }
            }
            biddingCheck.value = false
            websocket.send(JSON.stringify(data));
        }
    }
}
const setBidding = (place, card) => {
    if (card !== 'pass') {
        biddingList.value.push(card)
    }
    const name = player.value[place]
    if (card !== 'pass') {
        const nameDiv = document.createElement("div");
        nameDiv.classList.add("absolute", "top-0", "right-0", "text-xl", "font-bold", "bidding");
        nameDiv.innerHTML = name

        document.getElementById('bidding_' + card).appendChild(nameDiv)
    } else {
        const passList = document.getElementById('passList')
        const passListDiv = document.createElement("div");
        passListDiv.classList.add("flex", "passList", "items-center", "ml-2", "font-bold");
        passListDiv.innerHTML = name;

        passList.appendChild(passListDiv)
        passCount.value++
    }
}

const setBidder = (player, first) => {
    loadingStates.place_1.value = false;
    loadingStates.place_2.value = false;
    loadingStates.place_3.value = false;
    loadingStates.place_4.value = false;
    loadingStates.place_5.value = false;
    if (place.value === player) {
        isBidder.value = true
        isFirstBidder.value = first
    }

    const compare = placeCompare();
    const comparePlace = compare[player];
    if (loadingStates.hasOwnProperty(comparePlace)) {
        loadingStates[comparePlace].value = true;
    }
}
const setKing = (player, card) => {
    const compare = placeCompare();
    const place = compare[player];
    if (king.hasOwnProperty(place)) {
        king[place].value = true;
    }

    document.getElementById(place + '_king').innerHTML = card.split('_')[1] + ' ' + card.split('_')[0]
}
const playCard = (card) => {
    let able = true
    if (roundCard.value.length !== 0) {
        const suits = roundCard.value[0].split('_')[0];
        if (suits !== 'joker') {
            if (card.split('_')[0] !== suits) {
                handCards.value.forEach(function (hands) {
                    if (hands.split('_')[0] === suits) {
                        able = false
                    }
                })
            }
        }
    }
    if (ablePlay.value && able) {
        ablePlay.value = false
        const indexToRemove = handCards.value.indexOf(card);
        if (indexToRemove !== -1) {
            handCards.value.splice(indexToRemove, 1);
        }
        const data = {
            'action': 'play',
            'place': place.value,
            'round': round.value,
            'card': card
        }
        websocket.send(JSON.stringify(data));
        document.getElementById(card).remove()
        round.value++;
    }
}

const changeCard = (card) => {
    if (card === 'joker_black') {
        return
    }
    const div = document.getElementById(card);
    const index = riverCard.value.indexOf(card);

    if (index !== -1) {
        riverCard.value.splice(index, 1);

        div.classList.remove('border-2', 'border-red-800', 'rounded');
    } else {
        div.classList.add('border-2', 'border-red-800', 'rounded');
        riverCard.value.push(card);
    }

    document.getElementById('confirmChange').classList.add('hidden')
    if (riverCard.value.length === 3) {
        document.getElementById('confirmChange').classList.remove('hidden')
    }
}

const confirmChange = () => {
    const data = {
        'action': 'confirmChange',
        'place': place.value,
        'card': riverCard.value
    }

    websocket.send(JSON.stringify(data));
}
const exit = () => {

}

const placeCompare = () => {
    if (place.value === 'player_1' || character.value === 'observer') {
        return {
            'player_1': 'place_1',
            'player_2': 'place_2',
            'player_3': 'place_3',
            'player_4': 'place_4',
            'player_5': 'place_5',
        }
    }
    if (place.value === 'player_2') {
        return {
            'player_2': 'place_1',
            'player_3': 'place_2',
            'player_4': 'place_3',
            'player_5': 'place_4',
            'player_1': 'place_5',
        }
    }
    if (place.value === 'player_3') {
        return {
            'player_3': 'place_1',
            'player_4': 'place_2',
            'player_5': 'place_3',
            'player_1': 'place_4',
            'player_2': 'place_5',
        }
    }
    if (place.value === 'player_4') {
        return {
            'player_4': 'place_1',
            'player_5': 'place_2',
            'player_1': 'place_3',
            'player_2': 'place_4',
            'player_3': 'place_5',
        }
    }
    if (place.value === 'player_5') {
        return {
            'player_5': 'place_1',
            'player_1': 'place_2',
            'player_2': 'place_3',
            'player_3': 'place_4',
            'player_4': 'place_5',
        }
    }
}
const setAllPlayerName = (data) => {
    document.getElementById('place_1_name').innerHTML = ''
    document.getElementById('place_2_name').innerHTML = ''
    document.getElementById('place_3_name').innerHTML = ''
    document.getElementById('place_4_name').innerHTML = ''
    document.getElementById('place_5_name').innerHTML = ''
    let players = {};
    console.log(data)
    Object.values(data).forEach(function (element) {
        const compare = placeCompare();
        const place = compare[element['place']];
        document.getElementById(place + '_name').innerHTML = element['name']
        players[element['place']] = element['name']
    })

    player.value = players
}

const playLoading = (player) => {
    loadingStates.place_1.value = false;
    loadingStates.place_2.value = false;
    loadingStates.place_3.value = false;
    loadingStates.place_4.value = false;
    loadingStates.place_5.value = false;
    if (place.value === player) {
        ablePlay.value = true
    }

    const compare = placeCompare();
    const comparePlace = compare[player];
    if (loadingStates.hasOwnProperty(comparePlace)) {
        loadingStates[comparePlace].value = true;
    }
}
const generatePlayCard = (place, card) => {
    const table = document.getElementById('table')

    const div = document.createElement('div')
    div.classList.add('relative')
    const img = document.createElement('img')
    img.src = '/pokerImage/' + card + '.jpg'

    const name = player.value[place]
    const nameDiv = document.createElement('div')
    nameDiv.classList.add('absolute', 'top-0', 'right-0', 'text-xl', 'font-bold')
    nameDiv.innerHTML = name
    div.appendChild(img)
    div.appendChild(nameDiv)

    table.appendChild(div)
}

const removeHandsCard = (player) => {
    if (player !== place.value) {
        const compare = placeCompare();
        const place = compare[player];
        const place1Hands = document.getElementById(place + "_hands");
        const divToRemove = place1Hands.querySelector("div:first-child");
        if (divToRemove) {
            place1Hands.removeChild(divToRemove);
        }
    }
}

const showFinal = async (result) => {
    const finalImg = () => {
        return new Promise(function (resolve, reject) {
            setTimeout(function () {
                const table = document.getElementById('table')
                table.classList.add('hidden')
                table.innerHTML = ''
                const img = document.createElement('img')
                if (result === 'win') {
                    const randomIndex = Math.floor(Math.random() * win.value.length);
                    img.src = win.value[randomIndex]
                }
                if (result === 'lose') {
                    const randomIndex = Math.floor(Math.random() * lose.value.length);
                    img.src = lose.value[randomIndex]
                }
                const final = document.getElementById('final')
                final.classList.remove('hidden')

                const finalImg = document.getElementById('finalImg')
                finalImg.appendChild(img)

                resolve('finalImg')
            }, 2000)
        });
    }

    await finalImg()
    await clear()
}
const clear = () => {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {

            const final = document.getElementById('final')
            final.classList.add('hidden')

            const finalImg = document.getElementById('finalImg')
            finalImg.innerHTML = ''

            const dashboard = document.getElementById('dashboard')
            dashboard.classList.remove('hidden')

            isBidder.value = false

            isFirstBidder.value = false

            biddingCheck.value = false

            passCount.value = 0

            round.value = 1

            isKing.value = false

            riverCardsOpenCount.value = 0

            ablePlay.value = false

            roundCard.value = [];

            biddingList.value = [];

            loadingStates.place_1.value = false
            loadingStates.place_2.value = false
            loadingStates.place_3.value = false
            loadingStates.place_4.value = false
            loadingStates.place_5.value = false

            king.place_1.value = false
            king.place_2.value = false
            king.place_3.value = false
            king.place_4.value = false
            king.place_5.value = false

            const biddingDivs = document.querySelectorAll('.bidding');

            biddingDivs.forEach((div) => {
                div.remove();
            });

            document.getElementById('place_1_point').innerHTML = '';
            document.getElementById('place_2_point').innerHTML = '';
            document.getElementById('place_3_point').innerHTML = '';
            document.getElementById('place_4_point').innerHTML = '';
            document.getElementById('place_5_point').innerHTML = '';


            document.getElementById('place_1_hands').innerHTML = '';
            document.getElementById('place_2_hands').innerHTML = '';
            document.getElementById('place_3_hands').innerHTML = '';
            document.getElementById('place_4_hands').innerHTML = '';
            document.getElementById('place_5_hands').innerHTML = '';

            document.getElementById('place_1_king').innerHTML = ''
            document.getElementById('place_2_king').innerHTML = ''
            document.getElementById('place_3_king').innerHTML = ''
            document.getElementById('place_4_king').innerHTML = ''
            document.getElementById('place_5_king').innerHTML = ''

            document.getElementById('passList').innerHTML = '';

            document.getElementById("riverCard1").src = background;
            document.getElementById("riverCard2").src = background;
            document.getElementById("riverCard3").src = background;

            resolve('clear')
        }, 3000)
    });
}

</script>

<template>
    <div class="h-screen w-screen">
        <div class="grid grid-cols-2 grid-flow-col h-1/5 w-screen">
            <div class="col">
                <div class="flex flex-row justify-center" id="place_3_hands">
                    <!--                    <div class="-mr-5">-->
                    <!--                        <img :src="background">-->
                    <!--                    </div>-->
                </div>
                <div class="float-left flex text-2xl w-full items-center justify-center" id="place_3">
                    <font-awesome-icon :icon="['fas', 'chess-king']"
                                       style="color: #f8fc03"
                                       v-if="king.place_3.value"/>
                    <span class="mr-2" id="place_3_king"></span>
                    <span class="mr-2" id="place_3_name"></span>
                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse v-if="loadingStates.place_3.value"/>
                    <div class="flex" id="place_3_point">
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->

                    </div>
                </div>
            </div>
            <div class="col ">
                <div class="flex flex-row justify-center" id="place_4_hands">
                    <!--                    <div class="-mr-5">-->
                    <!--                        <img :src="background">-->
                    <!--                    </div>-->
                </div>
                <div class="text-2xl flex items-center justify-center" id="place_4">
                    <font-awesome-icon :icon="['fas', 'chess-king']"
                                       style="color: #f8fc03"
                                       v-if="king.place_4.value"/>
                    <span class="mr-2" id="place_4_king"></span>
                    <span class="mr-2" id="place_4_name"></span>
                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse v-if="loadingStates.place_4.value"/>
                    <div class="flex" id="place_4_point">
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->
                        <!--                        <img :src="background" class="h-10">-->
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-rows-4 grid-cols-8 grid-flow-col h-4/5 w-screen">
            <div class="row-span-4">
                <div class="float-right flex flex-col justify-center text-2xl h-full" id="place_2">
                    <font-awesome-icon :icon="['fas', 'chess-king']"
                                       style="color: #f8fc03"
                                       v-if="king.place_2.value"/>
                    <span class="mr-2" id="place_2_king"></span>
                    <span class="mr-2" id="place_2_name"></span>
                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse v-if="loadingStates.place_2.value"/>
                    <div id="place_2_point">
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                    </div>
                </div>
                <div id="place_2_hands">
                    <!--                <div class="-mb-10  flex justify-center items-center">-->
                    <!--                    <img src="/pokerImage/background_90.png">-->
                    <!--                </div>-->
                </div>
            </div>
            <div class="row-span-3 col-span-6  relative">
                <!--                <button class="bg-indigo-500" v-on:click="deal">發牌</button>-->
                <div class="h-full hidden" id="river_cards">
                    <div class="flex flex-row justify-center items-center h-full">
                        <img :src="background" class="h-1/3" id="riverCard1"
                             v-on:click="checkRiverCard(1)">
                        <img :src="background" class="h-1/3" id="riverCard2"
                             v-on:click="checkRiverCard(2)">
                        <img :src="background" class="h-1/3" id="riverCard3"
                             v-on:click="checkRiverCard(3)">
                    </div>
                </div>
                <div class="h-full hidden" id="choose_king">
                    <div class="flex flex-row justify-center w-auto h-1/6">
                        <div class="relative " id="bidding_club_1">
                            <img :src="club_A" class="h-full" v-on:click="bidding('club_1')">
                        </div>
                        <div class="relative" id="bidding_diamond_1">
                            <img :src="diamond_A" class="h-full" v-on:click="bidding('diamond_1')">
                        </div>
                        <div class="relative" id="bidding_heart_1">
                            <img :src="heart_A" class="h-full" v-on:click="bidding('heart_1')">
                        </div>
                        <div class="relative" id="bidding_spade_1">
                            <img :src="spade_A" class="h-full" v-on:click="bidding('spade_1')">
                        </div>
                    </div>
                    <div class="flex flex-row justify-center w-auto h-1/6">
                        <div class="relative" id="bidding_club_2">
                            <img :src="club_2" class="h-full" v-on:click="bidding('club_2')">
                        </div>
                        <div class="relative" id="bidding_diamond_2">
                            <img :src="diamond_2" class="h-full" v-on:click="bidding('diamond_2')">
                        </div>
                        <div class="relative" id="bidding_heart_2">
                            <img :src="heart_2" class="h-full" v-on:click="bidding('heart_2')">
                        </div>
                        <div class="relative" id="bidding_spade_2">
                            <img :src="spade_2" class="h-full" v-on:click="bidding('spade_2')">
                        </div>
                    </div>
                    <div class="flex flex-row justify-center w-auto h-1/6">
                        <div class="relative" id="bidding_club_3">
                            <img :src="club_3" class="h-full" v-on:click="bidding('club_3')">
                        </div>
                        <div class="relative" id="bidding_diamond_3">
                            <img :src="diamond_3" class="h-full" v-on:click="bidding('diamond_3')">
                        </div>
                        <div class="relative" id="bidding_heart_3">
                            <img :src="heart_3" class="h-full" v-on:click="bidding('heart_3')">
                        </div>
                        <div class="relative" id="bidding_spade_3">
                            <img :src="spade_3" class="h-full" v-on:click="bidding('spade_3')">
                        </div>
                    </div>
                    <div class="flex flex-row justify-center w-auto h-1/6">
                        <div class="relative" id="bidding_club_4">
                            <img :src="club_4" class="h-full" v-on:click="bidding('club_4')">
                        </div>
                        <div class="relative" id="bidding_diamond_4">
                            <img :src="diamond_4" class="h-full" v-on:click="bidding('diamond_4')">
                        </div>
                        <div class="relative" id="bidding_heart_4">
                            <img :src="heart_4" class="h-full" v-on:click="bidding('heart_4')">
                        </div>
                        <div class="relative" id="bidding_spade_4">
                            <img :src="spade_4" class="h-full" v-on:click="bidding('spade_4')">
                        </div>
                    </div>
                    <div class="flex flex-row justify-center w-auto h-1/6">
                        <div class="relative" id="bidding_club_5">
                            <img :src="club_5" class="h-full" v-on:click="bidding('club_5')">
                        </div>
                        <div class="relative" id="bidding_diamond_5">
                            <img :src="diamond_5" class="h-full" v-on:click="bidding('diamond_5')">
                        </div>
                        <div class="relative" id="bidding_heart_5">
                            <img :src="heart_5" class="h-full" v-on:click="bidding('heart_5')">
                        </div>
                        <div class="relative" id="bidding_spade_5">
                            <img :src="spade_5" class="h-full" v-on:click="bidding('spade_5')">
                        </div>
                    </div>
                    <div class="flex flex-row justify-center items-center w-auto h-1/6">
                        <div class="flex">
                            <button class="px-4 py-2 bg-red-500 text-3xl rounded-md hover:bg-red-600 h-fit"
                                    v-if="!isFirstBidder && passCount<4"
                                    v-on:click="bidding('pass')">pass
                            </button>
                        </div>
                        <div id="passList" class="flex">

                        </div>
                    </div>
                </div>
                <div class="flex flex-row justify-center items-center h-full hidden" id="table">
                </div>
                <div class="h-full hidden" id="characterSelectForm">
                    <div class="flex flex-row justify-center items-center h-full">
                        <div class="p-4 border rounded-lg shadow-md bg-indigo-50">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    角色:
                                </label>
                                <div class="flex items-center">
                                    <input type="radio" class="form-radio text-green-500" v-model="selectedCharacter"
                                           value="player" checked>
                                    <label class="ml-2 text-gray-700">玩家</label>
                                </div>
                                <div class="flex items-center mt-2">
                                    <input type="radio" class="form-radio text-green-500" v-model="selectedCharacter"
                                           value="observer">
                                    <label class="ml-2 text-gray-700">觀戰</label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                    名字:
                                </label>
                                <input type="text" id="name"
                                       class="form-input rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                       v-model="name">
                            </div>
                            <div>
                                <button class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                                        @click="characterConfirm">確認
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-full hidden" id="dashboard">
                    <div class="flex flex-row justify-center items-center h-full">
                        <div class="p-4 border rounded-lg shadow-md bg-indigo-50">
                            <div>
                                <button class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 mr-3"
                                        @click="requestDeal">發牌
                                </button>
                                <button class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-green-600"
                                        @click="exit">退出
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-full hidden" id="final">
                    <div class="flex flex-row justify-center items-center h-full">
                        <div class="h-full" id="finalImg">
                        </div>
                    </div>
                </div>

                <div class="h-full hidden" id="characterLoading">
                    <div class="flex flex-row justify-center items-center h-full">
                        <div class="p-10 border rounded-lg shadow-md bg-indigo-50">
                            <div class="mb-4">
                                <div class="flex items-center mt-2">
                                    <span v-if="character==='player'">等待其他玩家中...</span>
                                    <span v-if="character==='observer'">等待牌局開始中...</span>
                                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-full hidden" id="confirmChange">
                    <div class="flex flex-row justify-center items-center h-full">
                        <div class="p-4">
                            <div>
                                <button class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 mr-3"
                                        @click="confirmChange">確定
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-6">
                <div class="text-2xl w-full flex items-center justify-center" id="place_1">
                    <font-awesome-icon :icon="['fas', 'chess-king']"
                                       style="color: #f8fc03"
                                       v-if="king.place_1.value"/>
                    <span class="mr-2" id="place_1_king"></span>
                    <span class="mr-2" id="place_1_name">
                    </span>
                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse v-if="loadingStates.place_1.value"/>
                    <div class="flex" id="place_1_point">
                        <!--                        <img :src="background" class="h-10">-->
                    </div>
                </div>
                <div class="flex flex-row justify-center items-end" id="place_1_hands">

                    <!--                    <div class="-mr-3">-->
                    <!--                        <img src="/pokerImage/spade_A.jpg">-->
                    <!--                    </div>-->
                </div>
            </div>
            <div class="row-span-4  grid-rows-10">
                <div id="place_5" class="float-left text-2xl flex flex-col justify-center h-full">
                    <font-awesome-icon :icon="['fas', 'chess-king']"
                                       style="color: #f8fc03"
                                       v-if="king.place_5.value"/>
                    <span class="mr-2" id="place_5_king"></span>
                    <span class="mr-2" id="place_5_name"></span>
                    <font-awesome-icon icon="fa-solid fa-spinner" spin-pulse v-if="loadingStates.place_5.value"/>

                    <div id="place_5_point">
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                        <!--                        <img src="/pokerImage/background_90.png" class="h-10">-->
                    </div>
                </div>
                <div id="place_5_hands">
                    <!--                <div class="-mb-10  flex justify-center items-center">-->
                    <!--                    <img src="/pokerImage/background_90.png">-->
                    <!--                </div>-->
                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>

</style>
