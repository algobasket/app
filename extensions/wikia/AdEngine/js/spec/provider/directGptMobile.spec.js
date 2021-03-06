/*global beforeEach, describe, it, modules, expect, spyOn*/
describe('ext.wikia.adEngine.provider.directGpt', function () {
	'use strict';

	var noop = function () {},
		mocks = {
			adContext: {
				getContext: function () {
					return mocks.context;
				}
			},
			context: {
				opts: {

				}
			},
			uapContext: {},
			factory: {
				createProvider: noop
			},
			defaultAdUnitBuilder: {name: 'defaultAdUnit'},
			kiloAdUnitBuilder: {name: 'kiloAdUnit'},
			slotTweaker: {},
			openXBidderHelper: {},
			pageFairRecovery: {},
			sourcePointRecovery: {}
		};

	function getModule() {
		return modules['ext.wikia.adEngine.provider.directGpt'](
			mocks.adContext,
			mocks.uapContext,
			mocks.factory,
			mocks.defaultAdUnitBuilder,
			mocks.kiloAdUnitBuilder,
			mocks.slotTweaker,
			mocks.openXBidderHelper,
			mocks.pageFairRecovery,
			mocks.sourcePointRecovery
		);
	}

	it('Return default adUnit if there is no param in context', function () {
		spyOn(mocks.factory, 'createProvider');

		getModule();

		expect(mocks.factory.createProvider.calls.argsFor(0)[4].adUnitBuilder)
			.toEqual(mocks.defaultAdUnitBuilder);
	});

	it('Return KILO adUnit if there is param in context', function () {
		spyOn(mocks.factory, 'createProvider');
		spyOn(mocks.adContext, 'getContext').and.returnValue({opts: {enableKILOAdUnit: true}});

		getModule();

		expect(mocks.factory.createProvider.calls.argsFor(0)[4].adUnitBuilder)
			.toEqual(mocks.kiloAdUnitBuilder);
	});
});
