<?php

namespace tests;

class GetRequestTest extends TestCase
{
    use MockRequest;

    /**
     * @test
     */
    function successfully_get_track_as_json()
    {
        $response = $this->sendHTTP('GET', '/api/v1/track/1', null, 'application/json');
        $this->assertEquals(200, $response->getStatusCode());
        $track = '{"id":1,"mmsi":247039300,"status":0,"stationId":81,"speed":187,"lon":15.4415,"lat":42.7518,"course":144,"heading":144,"rot":"","timestamp":1372683960}';
        $this->assertEquals($track, (string)$response->getBody());
        $this->assertEquals('application/json', $response->getHeader('Content-type')[0]);
    }

    /**
     * @test
     */
    function successfully_get_track_as_xml()
    {
        $response = $this->sendHTTP('GET', '/api/v1/track/1', null, 'application/xml');
        $this->assertEquals(200, $response->getStatusCode());
        $track = '<root><id>1</id><mmsi>247039300</mmsi><status>0</status><stationId>81</stationId><speed>187</speed><lon>15.4415</lon><lat>42.7518</lat><course>144</course><heading>144</heading><rot/><timestamp>1372683960</timestamp></root>';
        $this->assertCOntains($track, (string)$response->getBody());
        $this->assertEquals('application/xml', $response->getHeader('Content-type')[0]);
    }

    /**
     * @test
     */
    function bad_request_when_track_id_not_valid()
    {
        $response = $this->sendHTTP('GET', '/api/v1/track/somestring', null, 'application/json');
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertContains('Invalid track id.', (string)$response->getBody());
    }
 
    /**
     * @test
     */  
    function bad_request_when_track_id_does_not_exist()
    {
        $response = $this->sendHTTP('GET', '/api/v1/track/99999', null, 'application/json');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertContains('No track found.', (string)$response->getBody());
    }
}