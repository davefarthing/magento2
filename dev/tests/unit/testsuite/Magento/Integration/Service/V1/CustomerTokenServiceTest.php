<?php
/**
 * Test for \Magento\Integration\Service\V1\CustomerTokenService
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */

namespace Magento\Integration\Service\V1;

use Magento\Integration\Model\Integration;
use Magento\Integration\Model\Oauth\Token;

class CustomerTokenServiceTest extends \PHPUnit_Framework_TestCase
{
    /** \Magento\Integration\Service\V1\CustomerTokenService */
    protected $_tokenService;

    /** \Magento\Integration\Model\Oauth\TokenFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenFactoryMock;

    /** \Magento\Customer\Api\AccountManagementInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $_accountManagementMock;

    /** \Magento\Integration\Model\Resource\Oauth\Token\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenModelCollectionMock;

    /** \Magento\Integration\Model\Resource\Oauth\Token\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenModelCollectionFactoryMock;

    /** @var \Magento\Integration\Helper\Validator|\PHPUnit_Framework_MockObject_MockObject */
    protected $validatorHelperMock;

    /** @var \Magento\Integration\Model\Oauth\Token|\PHPUnit_Framework_MockObject_MockObject */
    private $_tokenMock;

    protected function setUp()
    {
        $this->_tokenFactoryMock = $this->getMockBuilder('Magento\Integration\Model\Oauth\TokenFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_tokenFactoryMock->expects($this->any())->method('create')->will($this->returnValue($this->_tokenMock));

        $this->_accountManagementMock = $this
            ->getMockBuilder('Magento\Customer\Api\AccountManagementInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->_tokenMock = $this->getMockBuilder('Magento\Integration\Model\Oauth\Token')
            ->disableOriginalConstructor()
            ->setMethods(['getToken', 'loadByCustomerId', 'setRevoked', 'save', '__wakeup'])->getMock();

        $this->_tokenModelCollectionMock = $this->getMockBuilder(
            'Magento\Integration\Model\Resource\Oauth\Token\Collection'
        )->disableOriginalConstructor()->setMethods(
            ['addFilterByCustomerId', 'getSize', '__wakeup', '_beforeLoad', '_afterLoad', 'getIterator']
        )->getMock();

        $this->_tokenModelCollectionFactoryMock = $this->getMockBuilder(
            'Magento\Integration\Model\Resource\Oauth\Token\CollectionFactory'
        )->setMethods(['create'])->disableOriginalConstructor()->getMock();

        $this->_tokenModelCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->_tokenModelCollectionMock));

        $this->validatorHelperMock = $this->getMockBuilder(
            'Magento\Integration\Helper\Validator'
        )->disableOriginalConstructor()->getMock();

        $this->_tokenService = new \Magento\Integration\Service\V1\CustomerTokenService(
            $this->_tokenFactoryMock,
            $this->_accountManagementMock,
            $this->_tokenModelCollectionFactoryMock,
            $this->validatorHelperMock
        );
    }

    public function testRevokeCustomerAccessToken()
    {
        $customerId = 1;

        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with($customerId)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenModelCollectionMock->expects($this->any())
            ->method('getSize')
            ->will($this->returnValue(1));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$this->_tokenMock])));
        $this->_tokenModelCollectionMock->expects($this->any())
            ->method('_fetchAll')
            ->will($this->returnValue(1));
        $this->_tokenMock->expects($this->once())
            ->method('setRevoked')
            ->will($this->returnValue($this->_tokenMock));
        $this->_tokenMock->expects($this->once())
            ->method('save');

        $this->assertTrue($this->_tokenService->revokeCustomerAccessToken($customerId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage This customer has no tokens.
     */
    public function testRevokeCustomerAccessTokenWithoutCustomerId()
    {
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with(null)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenMock->expects($this->never())
            ->method('save');
        $this->_tokenMock->expects($this->never())
            ->method('setRevoked')
            ->will($this->returnValue($this->_tokenMock));
        $this->_tokenService->revokeCustomerAccessToken(null);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The tokens could not be revoked.
     */
    public function testRevokeCustomerAccessTokenCannotRevoked()
    {
        $exception = new \Exception();
        $customerId = 1;
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with($customerId)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(1));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$this->_tokenMock])));

        $this->_tokenMock->expects($this->never())
            ->method('save');
        $this->_tokenMock->expects($this->once())
            ->method('setRevoked')
            ->will($this->throwException($exception));
        $this->_tokenService->revokeCustomerAccessToken($customerId);
    }
}