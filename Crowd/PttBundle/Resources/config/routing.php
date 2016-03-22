<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

use Crowd\PttBundle\Routing\PttRouting;
use Crowd\PttBundle\Util\PttUtil;

$fields = PttUtil::pttConfiguration();

$pttRouting = new PttRouting();

return $pttRouting->routingCollection($fields['bundles']);