/*
We have to create below details table for adding real estate / property detail., there will be below columns  in this table l

Columns :- ID, User ID , Property Type ID , Country ID, State ID, County name , State name , city , latitude  Longitude , Address, Type ID, Type, Land square feet, Building Square feet , Amount,Created date.

In this table  latitude  Longitude  and Land square feet, Building Square feet will be optional. For this we have to create below API’S

1) For adding data
2) Updating data
3) Delete data
4) Get data :- in this api we need to array of response first one is for detail or user id which we sended in req. Second is  data which not related to this user, it means it should contain all data which is not related to particular user

We have to create one more table with below columns

Columns :-  ID,Real estate Id, User id.

for this table we have to create one API for adding data , in api we will send request ID and User ID.

PropertyList,PropertyInterestList
sql mai se get property type api banana hai
*/

/****** Object:  Table [dbo].[PropertyInterestList]    Script Date: 10/16/2017 11:43:26 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[PropertyInterestList](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [PropertyId] [int] NULL,
  [UserId] [int] NULL,
 CONSTRAINT [PK_PropertyInterestList] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

/****** Object:  Table [dbo].[PropertyList]    Script Date: 10/16/2017 11:43:26 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[PropertyList](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [UserId] [int] NULL,
  [PropertyTypeId] [int] NULL,
  [CountryId] [int] NULL,
  [StateId] [nchar](10) NULL,
  [CountryName] [nvarchar] (200) NULL,
  [StateName] [nvarchar] (200) NULL,
  [City] [nvarchar](200) NULL,
  [Latitude] [decimal] (10,7) NULL,
  [Longitude] [decimal] (10,7) NULL,
  [Address] [nvarchar] (200) NULL,
  [TypeId] [int] NULL,
  [Type] [nvarchar] (200) NULL,
  [LandSqrFt] [decimal] (10,2) NULL,
  [BuildingSqrFt] [decimal] (10,2) NULL,
  [Attachment1] [nvarchar] (max) NULL,
  [Attachment2] [nvarchar] (max) NULL,
  [Attachment3] [nvarchar] (max) NULL,
  [Amount] [decimal] (15,2) NULL,
  [CreateDate] [datetime2] (7) NULL,
 CONSTRAINT [PK_PropertyList] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[PropertyInterestList]  WITH CHECK ADD  CONSTRAINT [FK_PropertyInterestList_PropertyList] FOREIGN KEY([PropertyId])
REFERENCES [dbo].[PropertyList] ([Id])
GO
ALTER TABLE [dbo].[PropertyInterestList] CHECK CONSTRAINT [FK_PropertyInterestList_PropertyList]
GO
