import './bootstrap';

import {createApp} from "vue";
import Home from './Home.vue';
/* import the fontawesome core */
import {library} from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

/* import specific icons */
import {faSpinner, faChessKing} from '@fortawesome/free-solid-svg-icons'

/* add icons to the library */
library.add(faSpinner)
library.add(faChessKing)

const app = createApp({})
app.component('home-component', Home);
app.component('font-awesome-icon', FontAwesomeIcon)

app.mount("#app")
