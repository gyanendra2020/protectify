(function () {
	var ub = {};
    window.ub = ub;
    ub.userId = null;
	ub.lastId = 0;
    ub.origin = 'https://app.protectify.org';

    if (window.ubUserId === undefined) {
        throw new Error('The ubUserId variable should be set');
    }

    ub.userId = window.ubUserId;

	ub.injectUbIds = function (root = document) {
		if (!root.ubId) {
			root.ubId = ++ub.lastId;
		}

		for (var childNodeIndex = 0; childNodeIndex < root.childNodes.length; ++childNodeIndex) {
            var childNode = root.childNodes[childNodeIndex];
			ub.injectUbIds(childNode);
		}
	};

	ub.nodeStringPathToPath = function (nodeStringPath) {
		return (nodeStringPath.match(/[A-Z]{1}[a-z]*/g) || []).map(function (index) {
			return parseInt(index.toLowerCase().split('').map(function (char) {
				return '0123456789abcdefghijklmnop'['abcdefghijklmnopqrstuvwxyz'.indexOf(char)];
			}).join(''), 26);
		});
	};

	ub.nodePathToStringPath = function (nodePath) {
		return nodePath.map(function (index) {
			index = index.toString(26).split('').map(function (char) {
				return 'abcdefghijklmnopqrstuvwxyz'['0123456789abcdefghijklmnop'.indexOf(char)];
			}).join('');

			return index[0].toUpperCase() + index.slice(1);
		}).join('');
	};

	ub.getNodePath = function (node) {
		if (!node.parentNode) {
			return null;
		}

		var nodeIndex = Array.prototype.indexOf.call(node.parentNode.childNodes, node);

		if (node.parentNode.parentNode) {
			return ub.getNodePath(node.parentNode).concat(nodeIndex);
		}

		return [nodeIndex];
	};

	ub.getNodeStringPath = function (node) {
		return ub.nodePathToStringPath(ub.getNodePath(node));
	};

	ub.getNodeByPath = function (nodePath, root = document) {
		if (nodePath.length === 0) {
			return root;
		}

		return ub.getNodeByPath(nodePath.slice(1), root.childNodes[nodePath[0]]);
	};

	ub.getNodeByStringPath = function (nodeStringPath, root = document) {
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
			for (var childNodeIndex = 0; childNodeIndex < node.childNodes.length; ++childNodeIndex) {
                var childNode = node.childNodes[childNodeIndex];
				ub.replaceRelativePathsToAbsolute(childNode);
			}
		}
	};

	ub.replaceScriptElementsToNoScript = function (node, recursive = true) {
		if (node.tagName === 'SCRIPT' && ['', 'text/javascript', 'module'].indexOf(node.type) > -1) {
			var newNode = document.createElement('noscript');
			ub.copyNodeProperties(node, newNode);
			node.parentNode.replaceChild(newNode, node);
		} else {
			if (recursive) {
				for (var childNodeIndex = 0; childNodeIndex < node.childNodes.length; ++childNodeIndex) {
                    var childNode = node.childNodes[childNodeIndex];
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

		for (var childNodeIndex = 0; childNodeIndex < root.childNodes.length; ++childNodeIndex) {
            var childNode = root.childNodes[childNodeIndex];
			var foundNode = ub.findNodeByUbId(ubId, childNode);

			if (foundNode) {
				return foundNode;
			}
		}

		return null;
	};

	ub.copyNodeProperties = function (node0, node1) {
		var propertyNames = ['ubId'];

		for (var propertyNameIndex = 0; propertyNameIndex < propertyNames.length; ++propertyNameIndex) {
            var propertyName = propertyNames[propertyNameIndex];

			if (node0[propertyName] !== undefined) {
				node1[propertyName] = node0[propertyName];
			}
		}
	};

	ub.cloneNode = function (node) {
		var clonedNode = node.cloneNode(true);

		function copyProperties(node, clonedNode) {
			ub.copyNodeProperties(node, clonedNode);

			for (var childNodeIndex = 0; childNodeIndex < node.childNodes.length; ++childNodeIndex) {
				copyProperties(node.childNodes[childNodeIndex], clonedNode.childNodes[childNodeIndex]);
			}
		}

		copyProperties(node, clonedNode);
		return clonedNode;
    };

    ub.doesNodeContain = function (parentNode, childNode) {
        var currentNode = childNode.parentNode;

        for (currentNode = childNode.parentNode; currentNode !== null; currentNode = currentNode.parentNode) {
            if (currentNode === parentNode) {
                return true;
            }
        }

        return false;
    };

	ub.request = function (options, onResponse) {
		options.method ||= 'GET';
		options.query ||= {};
		options.body ||= null;

		var queryString = Object.keys(options.query)
			.map(function (key) { return encodeURIComponent(key) + '=' + encodeURIComponent(options.query[key]); })
			.join('&');

		var finalUrl = ub.origin + options.url + (queryString ? options.url.includes('?') ? '&' : '?' + queryString : '');
		var xhr = new XMLHttpRequest;

		xhr.onload = function () {
			var body;

			try {
				body = JSON.parse(xhr.responseText);
			} catch (error) {
				body = xhr.responseText;
			}

			console.log('< [' + options.method + '] ' + finalUrl, xhr.status, body);

			onResponse({
				statusCode: xhr.status,
				body,
			});
		};

		xhr.onerror = function (error) {
			console.error(error);
		};

		console.log('> [' + options.method + '] ' + finalUrl, options.body);
		xhr.open(options.method, finalUrl);
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.send(JSON.stringify(options.body));
    };

    ub.getNodeOuterContent = function (node) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            return node.outerHTML;
        } else if (node.nodeType === Node.TEXT_NODE) {
            return node.textContent;
        } else if (node.nodeText === Node.COMMENT_NODE) {
            return '<!--' + node.textContent + '-->';
        }

        return null;
    };

	// ---------------------------------------------------------------------- //

	var recorder = {};
	ub.recorder = recorder;
    recorder.events = [];
    recorder.formInputs = [];

	recorder.makePage = function () {
		var html = (new XMLSerializer).serializeToString(recorder.shadowDocument.doctype) + recorder.shadowDocument.documentElement.outerHTML;

		recorder.page = {
            id: null,
			key: null,
			url: window.location.href,
			title: recorder.shadowDocument.title,
			html,

			initialState: {
				size: { width: window.innerWidth, height: window.innerHeight },
				scroll: { top: window.scrollY, left: window.scrollX },
			},
		};
	};

	recorder.onDocumentChanged = function (mutationRecords) {
		recorder.onDocumentChanged.index = recorder.onDocumentChanged.index || 0;
		var index = recorder.onDocumentChanged.index;
		++recorder.onDocumentChanged.index;

		console.log('mutationRecords', mutationRecords);

		mutationRecords = mutationRecords.map(function (mr) {
			return {
				type: mr.type,
				target: mr.target,
				addedNodes: Array.prototype.slice.call(mr.addedNodes),
				removedNodes: Array.prototype.slice.call(mr.removedNodes),
				previousSibling: mr.previousSibling,
				nextSibling: mr.nextSibling,
				attributeName: mr.attributeName,
				attributeNamespace: mr.attributeNamespace,
				oldValue: mr.oldValue,

				newValue: (
					mr.type === 'attributes'
						? mr.target.getAttribute(mr.attributeName)
						: (mr.type === 'characterData' ? mr.target.textContent : null)
				),
			};
		});

		mutationRecords = mutationRecords.filter(function (mutationRecord0, mutationRecordIndex, mutationRecords) {
			if (mutationRecord0.type === 'attributes') {
				var mutationRecord1 = mutationRecords.slice(mutationRecordIndex + 1).find(function (mutationRecord1) {
					return (
						mutationRecord1.type === 'attributes' &&
						mutationRecord1.target === mutationRecord0.target &&
						mutationRecord1.attributeName === mutationRecord0.attributeName
					);
				});

				if (!mutationRecord1) {
					return true;
				}

				mutationRecord1.oldValue = mutationRecord0.oldValue;
				return false;
			}

			if (mutationRecord0.type === 'characterData') {
				var mutationRecord1 = mutationRecords.slice(mutationRecordIndex + 1).find(function (mutationRecord1) {
					return (
						mutationRecord1.type === 'characterData' &&
						mutationRecord1.target === mutationRecord0.target
					);
				});

				if (!mutationRecord1) {
					return true;
				}

				mutationRecord1.oldValue = mutationRecord0.oldValue;
				return false;
            }

			return true;
		});

		mutationRecords = mutationRecords.filter(function (mutationRecord) {
			if (mutationRecord.type === 'attributes') {
				return mutationRecord.newValue !== mutationRecord.oldValue;
			}

			if (mutationRecord.type === 'characterData') {
				return mutationRecord.newValue !== mutationRecord.oldValue;
			}

			return true;
        });

        mutationRecords.forEach(function (mutationRecord0, mutationRecord0Index) {
            if (mutationRecord0.type === 'childList') {
                mutationRecord0.addedNodes = mutationRecord0.addedNodes.filter(function (addedNode0, addedNode0Index) {
                    var hasBeenAlreadyAdded = mutationRecord0.addedNodes.slice(0, addedNode0Index).some(function (addedNode1) {
                        return ub.doesNodeContain(addedNode1, addedNode0);
                    });

                    if (hasBeenAlreadyAdded) {
                        console.log('HBAA1', addedNode0);
                        // alert('has been already added 1');
                        return false;
                    }

                    hasBeenAlreadyAdded = mutationRecords.slice(0, mutationRecord0Index).some(function (mutationRecord1) {
                        if (mutationRecord1.type !== 'childList') {
                            return false;
                        }

                        return mutationRecord1.addedNodes.some(function (addedNode1) {
                            return ub.doesNodeContain(addedNode1, addedNode0);
                        });
                    });

                    if (hasBeenAlreadyAdded) {
                        console.log('HBAA2', addedNode0);
                        // alert('has been already added 2');
                        return false;
                    }

                    return true;
                });
            }
        });

		for (var mutationRecordIndex = 0; mutationRecordIndex < mutationRecords.length; ++mutationRecordIndex) {
            var mutationRecord = mutationRecords[mutationRecordIndex];

			if (mutationRecord.type === 'attributes') {
                var targetShadowNode = ub.findNodeByUbId(mutationRecord.target.ubId, recorder.shadowDocument);

				console.log(
					'[' + index + '] attribute ' + mutationRecord.attributeName + ' changed value ' +
					'from [' + mutationRecord.oldValue + '] ' +
					'to [' + mutationRecord.newValue + ']',
					ub.getNodeStringPath(targetShadowNode),
					targetShadowNode.outerHTML.slice(0, 150)
				);

				recorder.addEvent({
					time: Date.now() - recorder.startedAt,
					type: 'attribute',
					path: ub.getNodeStringPath(targetShadowNode),
					name: mutationRecord.attributeName,

					data: {
						oldValue: mutationRecord.oldValue,
						newValue: mutationRecord.newValue,
					},
				});
			} else if (mutationRecord.type === 'characterData') {
                var targetShadowNode = ub.findNodeByUbId(mutationRecord.target.ubId, recorder.shadowDocument);

				console.log(
					'[' + index + '] text changed ' +
					'from [' + mutationRecord.oldValue + '] ' +
					'to [' + mutationRecord.newValue + ']',
					ub.getNodeStringPath(targetShadowNode),
					targetShadowNode
				);

				recorder.addEvent({
					time: Date.now() - recorder.startedAt,
					type: 'text',
					path: ub.getNodeStringPath(mutationRecord.target),

					data: {
						oldValue: mutationRecord.oldValue,
						newValue: mutationRecord.newValue,
					},
				});
			} else if (mutationRecord.type === 'childList') {
				console.log('============== ' + mutationRecords.indexOf(mutationRecord));
				console.log('[' + index + '] childNodes', mutationRecord.target.childNodes);
				console.log('[' + index + '] nextSibling', mutationRecord.nextSibling);
				console.log('[' + index + '] addedNodes', mutationRecord.addedNodes);
				console.log('[' + index + '] removedNodes', mutationRecord.removedNodes);

				if (mutationRecord.removedNodes.length > 0) {
					for (var removedNodeIndex = 0; removedNodeIndex < mutationRecord.removedNodes.length; ++removedNodeIndex) {
                        var removedNode = mutationRecord.removedNodes[removedNodeIndex];
						console.log('Removed node ubId: ' + removedNode.ubId);
						var removedShadowNode = ub.findNodeByUbId(removedNode.ubId, recorder.shadowDocument);
						console.log('Removed node in shadow document', removedShadowNode);
						var removedNodeStringPath = ub.getNodeStringPath(removedShadowNode);
                        removedShadowNode.remove();
                        var removedNodeOuterContent = ub.getNodeOuterContent(removedNode);

                        if (removedNodeOuterContent === null) {
                            console.error('Can\'t get outer content for this node', removedNode);
                            continue;
                        }

						console.log(
							'[' + index + '] removed node ' + removedNodeStringPath,
							removedNodeOuterContent.slice(0, 150)
						);

						recorder.addEvent({
							time: Date.now() - recorder.startedAt,
							type: 'remove',
							path: removedNodeStringPath,

							data: {
								value: (removedNode.tagName === 'SCRIPT' ? '<noscript></noscript>' : removedNodeOuterContent),
							},
						});
					}
				}

				if (mutationRecord.addedNodes.length > 0) {
					console.log('mutationRecord.target.ubId = ' + mutationRecord.target.ubId);
					var shadowTarget = ub.findNodeByUbId(mutationRecord.target.ubId, recorder.shadowDocument);
					var nextSiblingUbId = mutationRecord.nextSibling ? mutationRecord.nextSibling.ubId : null;
					var shadowNextSibling = ub.findNodeByUbId(nextSiblingUbId, recorder.shadowDocument);

					for (var addedNodeIndex = 0; addedNodeIndex < mutationRecord.addedNodes.length; ++addedNodeIndex) {
                        var addedNode = mutationRecord.addedNodes[addedNodeIndex];
						ub.injectUbIds(addedNode);
						var addedShadowNode = ub.cloneNode(addedNode);
						console.log(shadowTarget, '.insertBefore(', addedShadowNode, shadowNextSibling, ')');
						shadowTarget.insertBefore(addedShadowNode, shadowNextSibling);
                        var addedNodeStringPath = ub.getNodeStringPath(addedShadowNode);
                        var addedNodeOuterContent = ub.getNodeOuterContent(addedNode);

                        if (addedNodeOuterContent === null) {
                            console.error('Can\'t get outer content for this node', addedNode);
                            continue;
                        }

						console.log(
							'[' + index  + '] added node ' + addedNodeStringPath,
							addedNodeOuterContent.slice(0, 150)
                        );

						recorder.addEvent({
							time: Date.now() - recorder.startedAt,
							type: 'add',
							path: addedNodeStringPath,

							data: {
								value: (addedNode.tagName === 'SCRIPT' ? '<noscript></noscript>' : addedNodeOuterContent),
							},
						});
					}
				}
			} else {
				console.log(mutationRecord);
			}
		}
	};

	recorder.runMutationObserver = function () {
		recorder.mutationObserver = new MutationObserver(recorder.onDocumentChanged);

		recorder.mutationObserver.observe(document, {
			subtree: true,
			childList: true,
			attributes: true,
			attributeOldValue: true,
			characterData: true,
			characterDataOldValue: true,
		});
	};

	recorder.runEventListeners = function () {
		window.addEventListener('scroll', function () {
			recorder.addEvent({
				time: Date.now() - recorder.startedAt,
				type: 'scroll',

				data: {
					top: window.scrollY,
					left: window.scrollX,
				},
			});
		});

		window.addEventListener('resize', function () {
			recorder.addEvent({
				time: Date.now() - recorder.startedAt,
				type: 'size',

				data: {
					width: window.innerWidth,
					height: window.innerHeight,
				},
			});
        });

        console.log('Bind Mousemove Event')

		window.addEventListener('mousemove', function (event) {
            console.log('Mousemove Event');

			recorder.addEvent({
				time: Date.now() - recorder.startedAt,
				type: 'mouse',

				data: {
					x: event.x,
					y: event.y,
				},
			});
        });

        window.addEventListener('click', function (event) {
			recorder.addEvent({
				time: Date.now() - recorder.startedAt,
				type: 'click',

				data: {
					x: event.pageX,
					y: event.pageY,
				},
			});
		});

		window.addEventListener('input', function (event) {
            var shadowNode = ub.findNodeByUbId(event.target.ubId, recorder.shadowDocument);
            var inputTitle = null;

            if (event.target.id) {
                var inputLabel = document.querySelector('[for="' + event.target.id + '"]');

                if (inputLabel && inputLabel.innerText) {
                    inputTitle = inputLabel.innerText;
                }
            }

            if (!inputTitle && event.target.placeholder) {
                inputTitle = event.target.placeholder;
            }

            var isRadio = event.target.tagName === 'INPUT' && event.target.type === 'radio';
            var isCheckbox = event.target.tagName === 'INPUT' && event.target.type === 'checkbox';

            if (isRadio || isCheckbox) {
                shadowNode.checked = event.target.checked;
            } else {
                shadowNode.value = event.target.value;
            }

            recorder.addEvent({
                time: Date.now() - recorder.startedAt,
                type: 'input',
                path: ub.getNodeStringPath(shadowNode),
                data: isRadio || isCheckbox ? { checked: event.target.checked } : { value: event.target.value },
            });

            if (event.target.form && event.target.name) {
                recorder.addFormInput({
                    form: { ubId: shadowNode.form.ubId },
                    type: event.target.tagName === 'SELECT' ? 'select' : event.target.type,
                    title: inputTitle,
                    name: event.target.name,
                    value: event.target.value,
                });
            }
		});
	};

	recorder.eventPoster = {};
	recorder.eventPoster.isRunning = false;

	recorder.eventPoster.run = function () {
		if (!recorder.page.key) {
			return;
		}

		if (recorder.events.length === 0) {
			return;
		}

		if (recorder.eventPoster.isRunning) {
			return;
		}

		recorder.eventPoster.timeout = setTimeout(function () {
			var postingEvents = recorder.events;
			recorder.events = [];

			ub.request({
				method: 'POST',
                url: '/api/ub/pages/' + recorder.page.key + '/events',
				body: postingEvents,
			}, function (response) {
				recorder.eventPoster.isRunning = false;

				if (response.statusCode === 201) {
					//
				} else {
					console.log(response.statusCode);
					recorder.events = postingEvents.concat(recorder.events);
				}

				if (recorder.events.length > 0) {
					recorder.eventPoster.run();
				}
			});
		}, 1000);

		recorder.eventPoster.isRunning = true;
	};

	recorder.addEvent = function (data) {
		recorder.addEvent.index = recorder.addEvent.index || 0;
		data.index = recorder.addEvent.index;
		++recorder.addEvent.index;
		recorder.events.push(data);
		recorder.eventPoster.run();
	};

	recorder.getEvents = function () {
		return JSON.stringify(recorder.events);
    };

    recorder.formInputPoster = {};
	recorder.formInputPoster.isRunning = false;

	recorder.formInputPoster.run = function () {
		if (!recorder.page.key) {
			return;
        }

        recorder.optimizeFormInputs();

		if (recorder.formInputs.length === 0) {
			return;
		}

		if (recorder.formInputPoster.isRunning) {
			return;
		}

		recorder.formInputPoster.timeout = setTimeout(function () {
			var postingFormInputs = recorder.formInputs;
			recorder.formInputs = [];

			ub.request({
				method: 'POST',
                url: '/api/ub/pages/' + recorder.page.key + '/form_inputs',

				body: postingFormInputs.map(function (postingFormInput) {
                    return {
                        form: { ub_id: postingFormInput.form.ubId },
                        name: postingFormInput.name,
                        title: postingFormInput.title,
                        type: postingFormInput.type,
                        value: postingFormInput.value,
                    };
                }),
			}, function (response) {
				recorder.formInputPoster.isRunning = false;

				if (response.statusCode === 201) {
                    //
				} else {
					console.log(response.statusCode);
					recorder.formInputs = postingFormInputs.concat(recorder.formInputs);
				}

				if (recorder.formInputs.length > 0) {
					recorder.formInputPoster.run();
				}
			});
		}, 1000);

		recorder.formInputPoster.isRunning = true;
    };

    recorder.optimizeFormInputs = function () {
        recorder.formInputs = recorder.formInputs.filter(function (formInput0, formInput0Index, formInputs) {
            return !formInputs.slice(formInput0Index + 1).some(function (formInput1) {
                if (formInput0.form.ubId !== formInput1.form.ubId) {
                    return false;
                }

                if (formInput0.name !== formInput1.name) {
                    return false;
                }

                return true;
            });
        });
    };

    recorder.addFormInput = function (data) {
        recorder.formInputs.push(data);
		recorder.formInputPoster.run();
    };

	recorder.postPage = function () {
		console.log('recorder.postPage');

		ub.request({
			method: 'POST',
			url: '/api/users/' + ub.userId + '/ub/pages',

			body: {
				visitor: {
					id: parseInt(localStorage.getItem('ub.visitorKey') || 0),
					'user_agent': navigator.userAgent,
				},

				url: recorder.page.url,
				title: recorder.page.title,
				html: recorder.page.html,
				initial_state: recorder.page.initialState,
			},
		}, function (response) {
			if (response.statusCode === 201) {
				localStorage.setItem('ub.visitorKey', response.body.data.visitor.key);
				recorder.page.id = response.body.data.id;
				recorder.page.key = response.body.data.key;
				delete recorder.page.html;
				delete recorder.page.initialState;
                recorder.eventPoster.run();
                recorder.emitPageId();
			} else {
				console.log(response.statusCode);
				setTimeout(recorder.postPage, 1000);
			}
		});
    };

    recorder.emitPageId = function () {
        var pageIdAnchorElements = document.querySelectorAll('[data-ub-page-id-anchor]');

        for (var pageIdAnchorElementIndex = 0; pageIdAnchorElementIndex < pageIdAnchorElements.length; ++pageIdAnchorElementIndex) {
            var pageIdAnchorElement = pageIdAnchorElements[pageIdAnchorElementIndex];
            var pageIdAnchorName = pageIdAnchorElement.getAttribute('data-ub-page-id-anchor');

            if (pageIdAnchorName) {
                if (pageIdAnchorName === 'value') {
                    pageIdAnchorElement.value = recorder.page.id;
                } else {
                    pageIdAnchorElement.setAttribute(pageIdAnchorName, recorder.page.id);
                }
            }
        }

        if (recorder.onPageIdReady.subscribers) {
            recorder.onPageIdReady.subscribers.forEach(function (subscriber) {
                subscriber(recorder.page.id);
            });

            delete recorder.onPageIdReady.subscribers;
        }
    };

    recorder.onPageIdReady = function (subscriber) {
        if (recorder.page.id) {
            return subscriber(recorder.page.id);
        }

        recorder.onPageIdReady.subscribers = recorder.onPageIdReady.subscribers || [];

        if (recorder.onPageIdReady.subscribers.indexOf(subscriber) < 0) {
            recorder.onPageIdReady.subscribers.push(subscriber);
        }
    };

	recorder.start = function () {
		recorder.startedAt = Date.now();
		ub.injectUbIds(document);
		recorder.shadowDocument = ub.cloneNode(document);
		console.log(recorder.shadowDocument);
		ub.replaceScriptElementsToNoScript(recorder.shadowDocument);
		ub.replaceRelativePathsToAbsolute(recorder.shadowDocument);
		recorder.makePage();
		recorder.runMutationObserver();
		recorder.runEventListeners();
		console.log(recorder.page.html);
		recorder.postPage();
	};

	recorder.start();
})();
