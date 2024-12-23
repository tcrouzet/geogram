// apiService.js

function log(message = '') {
    if (DEBUG) {
        try {
            let functionName = new Error().stack?.split('\n')[2]?.trim()?.split(' ')[1] || 'unknown';
            functionName = functionName.replace("Proxy.", "");
            
            if (typeof message === 'object') {
                console.log(functionName + ':');
                console.log(message);
            } else {
                console.log(`${functionName}${message ? ': ' + message : ''}`);
            }
        } catch (e) {
            if (typeof message === 'object') {
                console.log(message);
            } else {
                console.log(message ? message : 'log');
            }
        }
    }
}


const apiService = {
    async call(view, params = {}, options = {}) {
        log(view + " api call");

        const formData = new FormData();
        formData.append('view', view);
        
        // Add parameters
        Object.entries(params).forEach(([key, value]) => {
            formData.append(key, value);
        });

        try {
            const response = await fetch('/api/', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                timeout: 30000
            });

            if (options.debug) {
                log('API Response:', await response.clone().text());
            }

            const data = await response.json();

            // Si session expirée et pas déjà réessayé
            if (data.status === 'error') {
                log(data.message);
                return {status: 'error', message: data.message};
            }

            log(view + " api success");
            return data;
        } catch (error) {
            console.error(`API Error (${view}):`, error);
            throw error;
        }
    }
};


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

    getUserData() {
        const store = Alpine.store('headerActions');
        return {
            route: store.route,
            isLoggedIn: store.isLoggedIn,
            user: store.user,
            isOnRoute: store.isOnRoute,
            userid: store.user?.userid,
            username: store.user?.username,
            userroute: store.user?.userroute,
            routeid: store.route?.routeid,
            component: store.component,
            userstory: store.userstory,
        };
    },

    async initComponent(component) {
        log("Init component");
        await this.waitForStore();
        log("header store OK");

        const data = this.getUserData();
        Object.assign(component, data);

        if (component.isLoggedIn) {
            log("Logged user");
            if (typeof component.onLogin === 'function') {
                await component.onLogin();
            }
        }

        return data;
    },

};