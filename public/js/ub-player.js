(() => {
	let ub = {};
	window.ub = ub;
	ub.lastId = 0;

	ub.injectUbIds = function (root) {
		if (!root.ubId) {
			root.ubId = ++ub.lastId;
		}

		for (let childNode of root.childNodes) {
			ub.injectUbIds(childNode);
		}
	};

	ub.nodeStringPathToPath = function (nodeStringPath) {
		return (nodeStringPath.match(/[A-Z]{1}[a-z]*/g) || []).map((index) => {
			return parseInt(index.toLowerCase().split('').map((char) => {
				return '0123456789abcdefghijklmnop'['abcdefghijklmnopqrstuvwxyz'.indexOf(char)];
			}).join(''), 26);
		});
	};

	ub.nodePathToStringPath = function (nodePath) {
		return nodePath.map((index) => {
			index = index.toString(26).split('').map((char) => {
				return 'abcdefghijklmnopqrstuvwxyz'['0123456789abcdefghijklmnop'.indexOf(char)];
			}).join('');

			return index[0].toUpperCase() + index.slice(1);
		}).join('');
	};

	ub.getNodePath = function (node) {
		if (!node.parentNode) {
			return null;
		}

		let nodeIndex = Array.prototype.indexOf.call(node.parentNode.childNodes, node);

		if (node.parentNode.parentNode) {
			return ub.getNodePath(node.parentNode).concat(nodeIndex);
		}

		return [nodeIndex];
	};

	ub.getNodeStringPath = function (node) {
		return ub.nodePathToStringPath(ub.getNodePath(node));
	};

	ub.getNodeByPath = function (nodePath, root) {
		if (nodePath.length === 0) {
			return root;
        }

        if (nodePath[0] >= root.childNodes.length) {
            return null;
        }

		return ub.getNodeByPath(nodePath.slice(1), root.childNodes[nodePath[0]]);
	};

	ub.getNodeByStringPath = function (nodeStringPath, root) {
		return ub.getNodeByPath(ub.nodeStringPathToPath(nodeStringPath), root);
	};

	ub.replaceRelativePathsToAbsolute = function (node, recursive = true) {
		if (node.nodeType === Node.ELEMENT_NODE) {
			if (node.tagName === 'SCRIPT' && node.hasAttribute('src')) {
				node.src = node.src;
			}

			if (node.tagName === 'LINK' && ['stylesheet', 'icon'].indexOf(node.rel) > -1 && node.hasAttribute('href')) {
				node.href = node.href;
			}

			if (node.tagName === 'IMG' && node.hasAttribute('src')) {
				node.src = node.src;
			}
		}

		if (recursive) {
			for (let childNode of node.childNodes) {
				ub.replaceRelativePathsToAbsolute(childNode);
			}
		}
	};

	ub.replaceScriptElementsToNoScript = function (node, recursive = true) {
		if (node.tagName === 'SCRIPT') {
			let newNode = document.createElement('noscript');
			ub.copyNodeProperties(node, newNode);
			node.parentNode.replaceChild(newNode, node);
		} else {
			if (recursive) {
				for (let childNode of node.childNodes) {
					ub.replaceScriptElementsToNoScript(childNode);
				}
			}
		}
	};

	ub.getNodeUbId = function (node) {
		if (!node.ubId) {
			node.ubId = ++ub.lastId;
		}

		return node.ubId;
	};

	ub.findNodeByUbId = function (ubId, root) {
		if (!ubId) {
			return null;
		}

		if (root.ubId === ubId) {
			return root;
		}

		for (let childNode of root.childNodes) {
			let foundNode = ub.findNodeByUbId(ubId, childNode);

			if (foundNode) {
				return foundNode;
			}
		}

		return null;
	};

	ub.copyNodeProperties = function (node0, node1) {
		let propertyNames = ['ubId'];

		for (let propertyName of propertyNames) {
			if (node0[propertyName] !== undefined) {
				node1[propertyName] = node0[propertyName];
			}
		}
	};

	ub.cloneNode = function (node) {
		let clonedNode = node.cloneNode(true);

		function copyProperties(node, clonedNode) {
			ub.copyNodeProperties(node, clonedNode);

			for (let childNodeIndex = 0; childNodeIndex < node.childNodes.length; ++childNodeIndex) {
				copyProperties(node.childNodes[childNodeIndex], clonedNode.childNodes[childNodeIndex]);
			}
		}

		copyProperties(node, clonedNode);
		return clonedNode;
	};

	ub.request = function (options, onResponse) {
		options.method ||= 'GET';
		options.query ||= {};
		options.body ||= null;

		const queryString = Object.keys(options.query)
			.map(key => `${encodeURIComponent(key)}=${encodeURIComponent(options.query[key])}`)
			.join('&');

		const finalUrl = options.url + (queryString ? options.url.includes('?') ? '&' : '?' + queryString : '');
		const xhr = new XMLHttpRequest;

		xhr.onload = () => {
			let body;

			try {
				body = JSON.parse(xhr.responseText);
			} catch (error) {
				body = xhr.responseText;
			}

			console.log(`< [${options.method}] ${finalUrl}`, xhr.status, body);

			onResponse({
				statusCode: xhr.status,
				body,
			});
		};

		xhr.onerror = (error) => {
			console.error(error);
		};

		console.log(`> [${options.method}] ${finalUrl}`, options.body);
		xhr.open(options.method, finalUrl);
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.send(JSON.stringify(options.body));
	};

	// ---------------------------------------------------------------------- //

	let player = {};
	ub.player = player;

	player.initialize = function (page) {
		player.events = [];
		player.page = page;
		player.currentTime = 0;
		player.lastEventId = 0;
		player.elements = {};
		player.elements.iframe = document.querySelector('.ub-player-iframe');
		player.elements.playButton = document.querySelector('.ub-player-controls-button.is-play');
		player.elements.pauseButton = document.querySelector('.ub-player-controls-button.is-pause');
		player.elements.timeline = document.querySelector('.ub-player-controls-timeline');
		player.elements.timelinePoint = document.querySelector('.ub-player-controls-timeline div');
		player.elements.currentTime = document.querySelector('.ub-player-controls-current-time');
		player.elements.totalTime = document.querySelector('.ub-player-controls-total-time');
		player.initializeIframe();
		player.setCurrentTime();
		player.setTotalTime();

		// player.onTimePointPositionUpdated = function (newPosition) {
		// 	let newTime = Math.floor(player.page.duration * newPosition);
		// 	player.moveTo(newTime);
		// };

		// player.elements.timelinePoint.addEventListener('mousedown', (event) => {
		// 	console.log('mousedown', event);
		// 	player.elements.timelinePoint.isMoving = true;
		// 	player.elements.timelinePoint.originalX = parseInt(player.elements.timelinePoint.style.left || 0);
		// 	player.elements.timelinePoint.originalClinetX = event.clientX;
		// });

		// document.addEventListener('mousemove', (event) => {
		// 	console.log('mousemove', event);

		// 	if (player.elements.timelinePoint.isMoving) {
		// 		let newLeft = player.elements.timelinePoint.originalX + 100 / player.elements.timeline.offsetWidth * (event.clientX - player.elements.timelinePoint.originalClinetX);
		// 		newLeft = Math.min(100, newLeft);
		// 		newLeft = Math.max(0, newLeft);
		// 		player.elements.timelinePoint.style.left = newLeft + '%';
		// 		player.onTimePointPositionUpdated(newLeft);
		// 	}
		// });

		// document.addEventListener('mouseup', (event) => {
		// 	console.log('mouseup', event);
		// 	delete player.elements.timelinePoint.isMoving;
		// });

		// player.elements.timeline.addEventListener('click', (event) => {
		// 	let wishedX = event.pageX - ub.player.elements.timeline.offsetLeft - player.elements.timelinePoint.offsetWidth / 2;
		// 	wishedX = Math.max(0, wishedX);
		// 	wishedX = Math.min(player.elements.timeline.offsetWidth, wishedX);
		// 	let newLeft = wishedX / player.elements.timeline.offsetWidth * 100;
		// 	player.elements.timelinePoint.style.left = newLeft + '%';
		// 	player.onTimePointPositionUpdated(newLeft);
		// });

		player.elements.playButton.addEventListener('click', (event) => {
			event.preventDefault();
			player.start();
		});

		player.elements.pauseButton.addEventListener('click', (event) => {
			event.preventDefault();
			player.pause();
		});

		player.preloadEvents();
	};

	player.initializeIframe = function () {
		player.elements.iframeDocument = player.elements.iframe.contentWindow.document;
		player.injectHelperElements();
	};

	player.moveTo = function (newTime) {
		console.log(newTime);

		// let timeOffset = newTime - player.currentTime;

		// if (timeOffset > 0) {
		// 	//
		// } else if (timeOffset < 0) {
		// 	timeOffset
		// }
	};

	player.preloadEvents = function () {
		ub.request({
			method: 'GET',
			url: '/api/ub/pages/' + player.page.key + '/events',

			query: {
				'from_id': player.lastEventId,
				'till_time': player.currentTime + 10000,
			},
		}, function (response) {
			if (response.body.data.length > 0) {
				player.events = player.events.concat(response.body.data);
				player.lastEventId = player.events[player.events.length - 1].id;
			}
		});
	};

	player.injectHelperElements = function () {
		let div = player.elements.iframeDocument.createElement('div');

		div.innerHTML = (
            '<style type="text/css">' +
                'html { scroll-behavior: smooth; }' +
                '.\\:ub\\:cursor { position: fixed; z-index: 9999999; background-image: url(\'/img/cursor.svg\'); width: 33px; height: 33px; background-position: -14px -9px; background-size: 50px 50px; visibility: hidden; }' +
                '.\\:ub\\:click { position: absolute; z-index: 9999998; width: 10px; height: 10px; margin-top: -5px; margin-left: -5px; border-radius: 50%; background-color: rgba(0, 0, 0, 0.5); opacity: 1; transform: scale(0); transition: transform .75s ease-out, opacity .75s ease-out; }' +
                '.\\:ub\\:click.\\:ub\\:click\\:activated { transform: scale(10); opacity: 0; }' +
            '</style>' +
			'<div id=":ub:cursor" class=":ub:cursor"></div>'
		);

		while (div.childNodes.length > 0) {
			player.elements.iframeDocument.body.appendChild(div.childNodes[0]);
		}

		player.elements.cursor = player.elements.iframeDocument.getElementById(':ub:cursor');
    };

    player.injectClickHelperElement = function (data) {
        player.elements.iframe.contentWindow.requestAnimationFrame(() => {
            let div = player.elements.iframeDocument.createElement('div');

            div.innerHTML = (
                '<div class=":ub:click" style="top: ' + data.y + 'px; left: ' + data.x + 'px;"></div>'
            );

            let click = div.childNodes[0];
            player.elements.iframeDocument.body.appendChild(click);

            setTimeout(function () {
                player.elements.iframe.contentWindow.requestAnimationFrame(() => {
                    click.classList.add(':ub:click:activated');
                });
            }, 0);

            setTimeout(function () {
                click.remove();
            }, 750);
        });
    };

	player.setTimelinePointPosition = function () {
		let percent = player.currentTime / player.page.duration * 100;
		player.elements.timelinePoint.style.left = percent + '%';
	};

	player.timeToString = function (time) {
		let totalSeconds = Math.ceil(time / 1000);
		let totalMinutes = Math.floor(totalSeconds / 60);
		let seconds = totalSeconds - totalMinutes * 60;
		let totalHours = Math.floor(totalMinutes / 60);
		let minutes = totalMinutes - totalHours * 60;
		let hours = totalHours;
		return hours + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
	};

	player.setCurrentTime = function () {
		player.elements.currentTime.innerText = player.timeToString(this.currentTime);
	};

	player.setTotalTime = function () {
		player.elements.totalTime.innerText = player.timeToString(this.page.duration);
	};

	player.start = function (events) {
		if (player.isReinitializing) {
			return;
		}

		player.elements.playButton.classList.add('is-hidden');
		player.elements.pauseButton.classList.remove('is-hidden');

		player.eventPreloader = setInterval(() => {
			player.preloadEvents();
		}, 1000);

		player.timeRunner = setInterval(() => {
			player.currentTime += 100;
			player.currentTime = Math.min(player.currentTime, player.page.duration);
			let eventIndex = 0;

			for (; eventIndex < player.events.length && player.events[eventIndex].time < player.currentTime; ++eventIndex) {
				let event = player.events[eventIndex];
				console.log(event);

				if (event.type === 'scroll') {
					player.elements.iframe.contentWindow.requestAnimationFrame(() => {
						player.elements.iframe.contentWindow.scrollTo(event.data.left, event.data.top);
					});
				} else if (event.type === 'mouse') {
                    player.elements.iframe.contentWindow.requestAnimationFrame(() => {
                        player.elements.cursor.style.left = event.data.x + 'px';
                        player.elements.cursor.style.top = event.data.y + 'px';
                        player.elements.cursor.style.visibility = 'visible';
                    });
                } else if (event.type === 'click') {
                    player.injectClickHelperElement(event.data);
				} else if (event.type === 'attribute') {
                    let node = ub.getNodeByStringPath(event.path, player.elements.iframeDocument);

                    if (node) {
                        if (node.setAttribute) {
                            if (event.data.newValue === null) {
                                node.removeAttribute(event.name);
                            } else {
                                node.setAttribute(event.name, event.data.newValue);
                            }
                        } else {
                            console.log(node, event);
                        }
                    } else {
                        console.error('Node not found');
                    }
				} else if (event.type === 'add') {
                    if (event.data.value) {
                        let path = ub.nodeStringPathToPath(event.path);
                        let parentNode = ub.getNodeByStringPath(event.path.slice(0, -1), player.elements.iframeDocument);

                        if (parentNode) {
                            let nextSibling = parentNode.childNodes[path[path.length - 1]] || null;
                            let wrapper = player.elements.iframeDocument.createElement('div');
                            wrapper.innerHTML = event.data.value;

                            try {
                                parentNode.insertBefore(wrapper.firstChild, nextSibling);
                            } catch (error) {
                                console.error(error);
                            }
                        } else {
                            console.error('Parent node not found', event);
                        }
                    }
				} else if (event.type === 'remove') {
                    let node = ub.getNodeByStringPath(event.path, player.elements.iframeDocument);

                    if (node) {
                        if (node.remove) {
                            node.remove();
                        } else {
                            console.error('Node does not have .remove method', node, node.remove);
                        }
                    } else {
                        console.error('Node not found', event);
                    }
				} else if (event.type === 'input') {
                    let node = ub.getNodeByStringPath(event.path, player.elements.iframeDocument);

                    if (node) {
                        if (event.data.value !== undefined) {
                            node.value = event.data.value;
                        }

                        if (event.data.checked !== undefined) {
                            node.checked = event.data.checked;
                        }
                    } else {
                        console.error('Node not found', event);
                    }
				} else {
					console.log(event);
				}
			}

			player.events = player.events.slice(eventIndex);

			window.requestAnimationFrame(() => {
				player.setTimelinePointPosition();
				player.setCurrentTime();
			});

			if (player.currentTime >= player.page.duration) {
				player.pause();
				player.isReinitializing = true;
				player.currentTime = 0;
				player.lastEventId = 0;
				player.preloadEvents();
				player.setTimelinePointPosition();
				player.setCurrentTime();
				let newIframe = player.elements.iframe.cloneNode();
				player.elements.iframe.parentNode.replaceChild(newIframe, player.elements.iframe);
				player.elements.iframe = newIframe;

				newIframe.onload = () => {
					player.isReinitializing = false;
					player.initializeIframe();
				};
			}
		}, 100);
	};

	player.pause = function () {
		clearInterval(player.eventPreloader);
		clearInterval(player.timeRunner);
		player.elements.pauseButton.classList.add('is-hidden');
		player.elements.playButton.classList.remove('is-hidden');
	};
})();
