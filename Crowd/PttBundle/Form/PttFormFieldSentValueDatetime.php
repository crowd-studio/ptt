<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldSentValueDatetime extends PttFormFieldSentValue
{
    public function value()
    {
        return \DateTime::createFromFormat('d/m/Y H:m:s', $this->sentData . ':00');
    }
}
