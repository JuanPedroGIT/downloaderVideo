import { createRouter, createWebHistory } from 'vue-router'
import HomePage     from '../pages/HomePage.vue'
import VideoPage    from '../pages/VideoPage.vue'
import DocumentPage from '../pages/DocumentPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/',        component: HomePage },
    { path: '/video',   component: VideoPage },
    { path: '/docs',    component: DocumentPage },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

export default router
