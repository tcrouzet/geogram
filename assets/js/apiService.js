// apiService.js

const apiService = {
    async call(view, params = {}, options = {}) {
        console.log(view + " api call");

        const formData = new URLSearchParams();
        formData.append('view', view);
        
        // Ajouter les paramètres
        Object.entries(params).forEach(([key, value]) => {
            formData.append(key, value);
        });

        try {
            const response = await fetch('/api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });

            if (options.debug) {
                console.log('API Response:', await response.clone().text());
            }

            const data = await response.json();
            
            if (data.status === 'error') {
                throw new Error(data.message);
            }
            console.log(view +" api success");
            return data;
        } catch (error) {
            console.error(`API Error (${view}):`, error);
            throw error;
        }
    }
};

// const initService = {
//     async waitForStore() {
//         if (!Alpine.store('headerActions').ended) {
//             await new Promise(resolve => {
//                 const checkStore = setInterval(() => {
//                     if (Alpine.store('headerActions').ended) {
//                         clearInterval(checkStore);
//                         resolve();
//                     }
//                 }, 100);
//             });
//         }
//     },

//     getUserData() {
//         const store = Alpine.store('headerActions');
//         return {
//             route: store.route,
//             isLoggedIn: store.isLoggedIn,
//             user: store.user,
//             isOnRoute: store.isOnRoute,
//             userid: store.user?.userid,
//             username: store.user?.username,
//             userroute: store.user?.userroute,
//             routeid: store.route?.routeid,
//             userstory: store.userstory,
//             canPost: this.isPostPossible()
//         };
//     },

//     async initComponent(component) {
//         console.log("Init component");
//         await this.waitForStore();
//         console.log("header store OK");

//         const data = this.getUserData();
//         Object.assign(component, data);

//         if (component.isLoggedIn) {
//             console.log("Logged user");
//             if (typeof component.onLogin === 'function') {
//                 await component.onLogin();
//             }
//         }

//         return data;
//     },

//     isRouteActive() {
//         const now = new Date();
//         if (this.route.routestop) {
//             const stopDate = new Date(this.route.routestop);
//             return stopDate > now;
//         }
//         return true;  // Si pas de date de fin, la route est active
//     },

//     isPostPossible() {
//         console.log("isPostPossible");
//         if (!this.isLoggedIn) return false;

//         if (!this.isRouteActive()) return false;

//         if (this.user.userroute != this.route.routeid){
//             //Not connected to the route
//             return false;
//         }
        
//         // Route publique, tout utilisateur connecté peut poster
//         if(this.route.routestatus === 0) return true;

//         // Route visible ou privée, seuls les invités peuvent publier
//         if(this.route.routestatus > 0 && this.user.constatus == 2) return true;

//         return false;
//     },

// };

const initService = {
    async waitForStore() {
        if (!Alpine.store('headerActions').ended) {
            await new Promise(resolve => {
                const checkStore = setInterval(() => {
                    if (Alpine.store('headerActions').ended) {
                        clearInterval(checkStore);
                        resolve();
                    }
                }, 100);
            });
        }
    },

    isRouteActive(route) {
        const now = new Date();
        if (route.routestop) {
            const stopDate = new Date(route.routestop);
            return stopDate > now;
        }
        return true;
    },

    isPostPossible(data) {
        if (!data.isLoggedIn) return false;

        if (!this.isRouteActive(data.route)) return false;

        if (data.user.userroute != data.route.routeid) {
            return false;
        }
        
        if (data.route.routestatus === 0) return true;

        if (data.route.routestatus > 0 && data.user.constatus == 2) return true;

        return false;
    },

    getUserData() {
        const store = Alpine.store('headerActions');
        const data = {
            route: store.route,
            isLoggedIn: store.isLoggedIn,
            user: store.user,
            isOnRoute: store.isOnRoute,
            userid: store.user?.userid,
            username: store.user?.username,
            userroute: store.user?.userroute,
            routeid: store.route?.routeid,
            userstory: store.userstory
        };
        data.canPost = this.isPostPossible(data);
        return data;
    },

    async initComponent(component) {
        console.log("Init component");
        await this.waitForStore();
        console.log("header store OK");

        const data = this.getUserData();
        Object.assign(component, data);

        if (component.isLoggedIn) {
            console.log("Logged user");
            if (typeof component.onLogin === 'function') {
                await component.onLogin();
            }
        }

        return data;
    }
};
