-- ============================================================================
-- Copyright (C) 2012 Mikael Carlavan  <mcarlavan@qis-network.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================


CREATE TABLE IF NOT EXISTS `llx_ndfp_tva_det`(
  `rowid`			   int(11) AUTO_INCREMENT,
  `fk_ndfp`		   	   int(11)  NOT NULL,
  `fk_ndfp_det`		   int(11)  NOT NULL,
  `fk_ndfp_tax_det`	   int(11)  NOT NULL,
  `fk_user_author`	   int(11)  NOT NULL,      
  `fk_tva`			   double(24,8)  DEFAULT 0,  
  `total_tva`          double(24,8) DEFAULT 0,   	
  `tms`				   timestamp NOT NULL,   
  PRIMARY KEY (`rowid`)   
)ENGINE=innodb DEFAULT CHARSET=utf8 ;


